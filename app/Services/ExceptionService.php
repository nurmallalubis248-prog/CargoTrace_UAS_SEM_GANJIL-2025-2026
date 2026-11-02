<?php

namespace App\Services;

use App\Enums\ExceptionStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    public function __construct(
        private AuditService $audit,
        private NotificationService $notifications,
    ) {
    }

    /** Open an exception: stores detection time and adds an EXCEPTION marker to the shipment timeline. */
    public function open(Shipment $shipment, array $data, User $actor, ?Carbon $at = null): ShipmentException
    {
        if ($shipment->current_status === ShipmentStatus::CANCELLED) {
            throw new BusinessRuleException('Cannot open an exception on a cancelled shipment.');
        }

        return DB::transaction(function () use ($shipment, $data, $actor, $at) {
            $at ??= now();

            $exception = new ShipmentException($data); // type, severity, description only
            $exception->shipment_id = $shipment->id;
            $exception->status = ExceptionStatus::OPEN;
            $exception->detected_at = $at;
            $exception->reported_by = $actor->id;
            $exception->save();

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'event_type' => TrackingEvent::TYPE_EXCEPTION,
                'location_label' => $shipment->current_location_label,
                'latitude' => $shipment->current_latitude,
                'longitude' => $shipment->current_longitude,
                'occurred_at' => $at,
                'description' => "[{$exception->type->value}] ".$exception->description,
                'is_exception' => true,
                'created_by' => $actor->id,
            ]);

            $this->notifications->notifyShipmentParties(
                $shipment, 'EXCEPTION_OPENED',
                "Exception {$exception->type->value} ({$exception->severity->value}) on shipment {$shipment->shipment_number}.",
                $actor
            );
            $this->audit->log($actor, 'exception.opened', $exception, [
                'shipment_id' => $shipment->id, 'type' => $exception->type->value,
            ]);

            return $exception;
        });
    }

    /** Update status / severity / notes. Status may only move forward: OPEN -> INVESTIGATING -> RESOLVED. */
    public function update(ShipmentException $exception, array $data, User $actor, ?Carbon $at = null): ShipmentException
    {
        return DB::transaction(function () use ($exception, $data, $actor, $at) {
            $changes = [];

            if (isset($data['status'])) {
                $next = ExceptionStatus::from($data['status']);
                if (! $exception->status->canTransitionTo($next)) {
                    throw new BusinessRuleException(
                        "Invalid exception status change: {$exception->status->value} -> {$next->value}."
                    );
                }
                if ($next !== $exception->status) {
                    $changes['status'] = ['from' => $exception->status->value, 'to' => $next->value];
                    $exception->status = $next;
                    if ($next === ExceptionStatus::RESOLVED) {
                        $exception->resolved_at = $at ?? now();
                    }
                }
            }

            if (isset($data['severity']) && $data['severity'] !== $exception->severity->value) {
                $changes['severity'] = ['from' => $exception->severity->value, 'to' => $data['severity']];
                $exception->severity = $data['severity'];
            }

            if (array_key_exists('resolution_notes', $data) && $data['resolution_notes'] !== null) {
                $exception->resolution_notes = $data['resolution_notes'];
                $changes['resolution_notes'] = 'updated';
            }

            $exception->save();

            if ($changes) {
                $shipment = $exception->shipment;
                if (isset($changes['status']) && $exception->status === ExceptionStatus::RESOLVED) {
                    $this->notifications->notifyShipmentParties(
                        $shipment, 'EXCEPTION_RESOLVED',
                        "Exception {$exception->type->value} on shipment {$shipment->shipment_number} was resolved.", $actor
                    );
                }
                $this->audit->log($actor, 'exception.updated', $exception, $changes);
            }

            return $exception;
        });
    }
}
