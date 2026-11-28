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
use Illuminate\Support\Str;

class ShipmentService
{
    public function __construct(
        private EtaService $eta,
        private AuditService $audit,
        private NotificationService $notifications,
        private TrackingService $tracking,
    ) {
    }

    /**
     * Create a shipment together with its first tracking event.
     *  - admin / operator: status CREATED (the simulator books it automatically), the other staff role is notified.
     *  - customer: a request, status PENDING (waiting for confirmation); every admin and operator is notified.
     */
    public function create(array $data, User $actor, ?Carbon $at = null): Shipment
    {
        return DB::transaction(function () use ($data, $actor, $at) {
            $items = $data['items'] ?? [];
            $at ??= now();
            $isRequest = $actor->isCustomer();
            if ($isRequest) {
                $data['customer_id'] = $actor->id;   // a customer can only request for themselves
            }

            $origin = Location::findOrFail($data['origin_id']);

            $shipment = new Shipment(\Illuminate\Support\Arr::except($data, ['items'])); // 'items' is a relation, never a column
            $shipment->shipment_number = $this->generateNumber();
            $shipment->created_by = $actor->id;
            $shipment->current_status = $isRequest ? ShipmentStatus::PENDING : ShipmentStatus::CREATED;
            $shipment->estimated_arrival_at = $data['expected_arrival_at'] ?? null;   // customer requests have no schedule yet
            $shipment->current_latitude = $origin->latitude;
            $shipment->current_longitude = $origin->longitude;
            $shipment->current_location_label = $origin->name;
            $shipment->last_event_at = $at;
            $shipment->save();

            foreach ($items as $item) {
                $shipment->items()->create($this->normalizeItem($item));
            }

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => TrackingEvent::TYPE_STATUS_UPDATE,
                'status' => $shipment->current_status->value,
                'location_id' => $origin->id,
                'location_label' => $origin->name,
                'latitude' => $origin->latitude,
                'longitude' => $origin->longitude,
                'occurred_at' => $at,
                'description' => $isRequest ? 'Shipment request submitted by the customer.' : 'Shipment created.',
                'created_by' => $actor->id,
            ]);

            $this->eta->refresh($shipment);

            $this->audit->log($actor, $isRequest ? 'shipment.requested' : 'shipment.created', $shipment, [
                'shipment_number' => $shipment->shipment_number,
                'customer_id' => $shipment->customer_id,
            ]);

            if ($isRequest) {
                $this->notifications->notifyRequestSubmitted($shipment->setRelation('customer', $actor), $actor);
            } else {
                $this->notifications->notifyShipmentParties(
                    $shipment, 'SHIPMENT_CREATED', "Shipment {$shipment->shipment_number} has been created.", $actor
                );
                $this->notifications->notifyStaffCrossover($shipment->loadMissing('customer'), $actor);
            }

            return $shipment;
        });
    }

    /** Update master data. Allowed only before pickup (PENDING / CREATED / BOOKED). Status is never changed here. */
    public function update(Shipment $shipment, array $data, User $actor): Shipment
    {
        if (! in_array($shipment->current_status, [ShipmentStatus::PENDING, ShipmentStatus::CREATED, ShipmentStatus::BOOKED], true)) {
            throw new BusinessRuleException(
                "Shipment master data can only be edited while status is PENDING, CREATED or BOOKED (current: {$shipment->current_status->value})."
            );
        }

        return DB::transaction(function () use ($shipment, $data, $actor) {
            $items = $data['items'] ?? null;

            $shipment->fill(\Illuminate\Support\Arr::except($data, ['items']));
            if ($shipment->isDirty('expected_arrival_at')) {
                // Nothing has moved yet, so the ETA follows the new plan.
                $shipment->estimated_arrival_at = $shipment->expected_arrival_at;
            }

            $changes = [];
            foreach ($shipment->getDirty() as $field => $new) {
                $changes[$field] = ['from' => $shipment->getOriginal($field), 'to' => $new];
            }

            $shipment->save();

            if (is_array($items)) {
                $shipment->items()->delete();
                foreach ($items as $item) {
                    $shipment->items()->create($this->normalizeItem($item));
                }
                $changes['items'] = 'replaced';
            }

            $this->eta->refresh($shipment);
            $this->audit->log($actor, 'shipment.updated', $shipment, json_decode(json_encode($changes), true));

            return $shipment;
        });
    }

    /**
     * Staff process a customer request: PENDING -> BOOKED (recorded as a tracking event, so the customer is notified
     * by TrackingService). The other staff role is notified as well (operator -> admins, admin -> operators).
     */
    public function approve(Shipment $shipment, User $actor): Shipment
    {
        if (! $actor->isStaff()) {
            throw new BusinessRuleException('Only an admin or operator can process a shipment request.');
        }
        if ($shipment->current_status !== ShipmentStatus::PENDING) {
            throw new BusinessRuleException("Only shipments in status PENDING can be approved (current: {$shipment->current_status->value}).");
        }
        if (! $shipment->expected_departure_at || ! $shipment->expected_arrival_at) {
            throw new BusinessRuleException('Set the expected departure and arrival dates before approving the request.');
        }

        return DB::transaction(function () use ($shipment, $actor) {
            $role = $actor->isAdmin() ? 'admin' : 'operator';
            $this->tracking->addEvent($shipment, [
                'event_type' => TrackingEvent::TYPE_STATUS_UPDATE,
                'status' => ShipmentStatus::BOOKED->value,
                'location_id' => $shipment->origin_id,
                'description' => "Shipment request approved by the {$role}.",
            ], $actor);

            $shipment->refresh()->loadMissing('customer');
            $this->audit->log($actor, 'shipment.approved', $shipment, ['shipment_number' => $shipment->shipment_number]);
            $this->notifications->notifyStaffCrossover($shipment, $actor);

            return $shipment;
        });
    }

    /** Only shipments that have not been booked yet (CREATED, or a PENDING request) may be deleted (soft delete). */
    public function delete(Shipment $shipment, User $actor): void
    {
        if (! in_array($shipment->current_status, [ShipmentStatus::PENDING, ShipmentStatus::CREATED], true)) {
            throw new BusinessRuleException('Only shipments in status PENDING or CREATED can be deleted.');
        }

        $this->audit->log($actor, 'shipment.deleted', $shipment, ['shipment_number' => $shipment->shipment_number]);
        $shipment->delete();
    }

    private function normalizeItem(array $item): array
    {
        $item['quantity'] = $item['quantity'] ?? 1; // column is NOT NULL

        return $item;
    }

    private function generateNumber(): string
    {
        do {
            $number = 'CT'.now()->format('ymd').'-'.strtoupper(Str::random(5));
        } while (Shipment::withTrashed()->where('shipment_number', $number)->exists());

        return $number;
    }
}
