<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    public function __construct(
        private EtaService $eta,
        private AuditService $audit,
        private NotificationService $notifications,
    ) {
    }

    /**
     * Append a tracking event. Business rules enforced here (not in the controller):
     *  - no events on terminal shipments (DELIVERED / CANCELLED)
     *  - event time not in the future and not earlier than the latest existing event
     *  - STATUS_UPDATE must be a valid transition of the state machine
     * Existing events are never modified.
     */
    public function addEvent(Shipment $shipment, array $data, User $actor): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);

            if ($shipment->current_status->isTerminal()) {
                throw new BusinessRuleException(
                    "Shipment is {$shipment->current_status->value}; no more tracking events can be added."
                );
            }

            $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();

            if ($occurredAt->gt(now()->addMinute())) {
                throw new BusinessRuleException('Event time cannot be in the future.');
            }

            $last = $shipment->trackingEvents()->orderByDesc('occurred_at')->orderByDesc('id')->first();
            if ($last && $occurredAt->lt($last->occurred_at)) {
                throw new BusinessRuleException(
                    'Event time cannot be earlier than the latest event ('.$last->occurred_at->format('Y-m-d H:i').' UTC).'
                );
            }

            $type = $data['event_type'];
            $newStatus = null;

            if ($type === TrackingEvent::TYPE_STATUS_UPDATE) {
                $newStatus = ShipmentStatus::from($data['status']);
                if (! $shipment->current_status->canTransitionTo($newStatus)) {
                    $allowed = implode(', ', array_map(fn ($s) => $s->value, $shipment->current_status->allowedNext()));
                    throw new BusinessRuleException(
                        "Invalid status transition: {$shipment->current_status->value} -> {$newStatus->value}. Allowed next: {$allowed}."
                    );
                }
            }

            if ($newStatus === ShipmentStatus::DELIVERED) {
                // Delivery = the customer confirmed receipt (an admin may do it on their behalf), and only after paying.
                if ((int) $actor->id !== (int) $shipment->customer_id && ! $actor->isAdmin()) {
                    throw new BusinessRuleException('Only the customer (or an admin) can confirm receipt of the cargo.');
                }
                if (! $shipment->payments()->where('status', 'PAID')->exists()) {
                    throw new BusinessRuleException('Receipt can only be confirmed after the freight payment is PAID.');
                }
            }

            // Resolve coordinates: explicit lat/lng > chosen location.
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            $label = $data['location_label'] ?? null;
            $locationId = $data['location_id'] ?? null;

            if ($locationId) {
                $location = Location::findOrFail($locationId);
                $latitude ??= $location->latitude;
                $longitude ??= $location->longitude;
                $label ??= $location->name;
            }

            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => $type,
                'status' => $newStatus?->value,
                'location_id' => $locationId,
                'location_label' => $label,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'occurred_at' => $occurredAt,
                'description' => $data['description'] ?? null,
                'is_exception' => false,
                'created_by' => $actor->id,
            ]);

            // Update the shipment snapshot.
            if ($newStatus) {
                $shipment->current_status = $newStatus;
                if ($newStatus === ShipmentStatus::ARRIVED) {
                    $shipment->actual_arrival_at = $occurredAt;      // arrival time drives the delay, not the confirmation time
                }
                if ($newStatus === ShipmentStatus::DELIVERED) {
                    $shipment->actual_arrival_at ??= $occurredAt;
                }
            }
            if ($latitude !== null && $longitude !== null) {
                $shipment->current_latitude = $latitude;
                $shipment->current_longitude = $longitude;
                $shipment->current_location_label = $label;
            }
            if (! empty($data['estimated_arrival_at'])) {
                $shipment->estimated_arrival_at = Carbon::parse($data['estimated_arrival_at']);
            }
            $shipment->last_event_at = $occurredAt;
            $shipment->save();

            $becameDelayed = $this->eta->refresh($shipment);

            if ($newStatus) {
                $no = $shipment->shipment_number;
                $message = match ($newStatus) {
                    ShipmentStatus::ARRIVED => "Shipment {$no} has arrived. Please pay the freight, then confirm receipt.",
                    ShipmentStatus::DELIVERED => "Shipment {$no} is DELIVERED: receipt was confirmed.",
                    default => "Shipment {$no} is now {$newStatus->value}.",
                };
                $this->notifications->notifyShipmentParties($shipment, 'STATUS_CHANGED', $message, $actor);
                if ($newStatus === ShipmentStatus::DELIVERED) {
                    $this->notifications->notifyAdmins('DELIVERY_CONFIRMED', "Shipment {$no} was confirmed as received by the customer.", $shipment, $actor);
                }
            }
            if ($becameDelayed) {
                $this->notifications->notifyShipmentParties(
                    $shipment, 'SHIPMENT_DELAYED',
                    sprintf('Shipment %s is delayed by %.1f h (%s).', $shipment->shipment_number, $shipment->delay_hours, $shipment->delay_severity),
                    $actor
                );
            }

            $this->audit->log($actor, 'tracking.added', $shipment, [
                'event_id' => $event->id, 'event_type' => $type, 'status' => $newStatus?->value,
            ]);

            return $event;
        });
    }
}
