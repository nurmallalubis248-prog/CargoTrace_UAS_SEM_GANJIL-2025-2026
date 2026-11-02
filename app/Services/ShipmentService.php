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
    ) {
    }

    /** Create a shipment in status CREATED together with its first tracking event. */
    public function create(array $data, User $actor, ?Carbon $at = null): Shipment
    {
        return DB::transaction(function () use ($data, $actor, $at) {
            $items = $data['items'] ?? [];
            $at ??= now();

            $origin = Location::findOrFail($data['origin_id']);

            $shipment = new Shipment($data); // only $fillable fields are accepted
            $shipment->shipment_number = $this->generateNumber();
            $shipment->created_by = $actor->id;
            $shipment->current_status = ShipmentStatus::CREATED;
            $shipment->estimated_arrival_at = $data['expected_arrival_at'];
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
                'status' => ShipmentStatus::CREATED->value,
                'location_id' => $origin->id,
                'location_label' => $origin->name,
                'latitude' => $origin->latitude,
                'longitude' => $origin->longitude,
                'occurred_at' => $at,
                'description' => 'Shipment created.',
                'created_by' => $actor->id,
            ]);

            $this->eta->refresh($shipment);

            $this->audit->log($actor, 'shipment.created', $shipment, [
                'shipment_number' => $shipment->shipment_number,
                'customer_id' => $shipment->customer_id,
            ]);
            $this->notifications->notifyShipmentParties(
                $shipment, 'SHIPMENT_CREATED', "Shipment {$shipment->shipment_number} has been created.", $actor
            );

            return $shipment;
        });
    }

    /** Update master data. Allowed only before pickup (CREATED / BOOKED). Status is never changed here. */
    public function update(Shipment $shipment, array $data, User $actor): Shipment
    {
        if (! in_array($shipment->current_status, [ShipmentStatus::CREATED, ShipmentStatus::BOOKED], true)) {
            throw new BusinessRuleException(
                "Shipment master data can only be edited while status is CREATED or BOOKED (current: {$shipment->current_status->value})."
            );
        }

        return DB::transaction(function () use ($shipment, $data, $actor) {
            $items = $data['items'] ?? null;

            $shipment->fill($data);
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

    /** Only shipments that have not been booked yet may be deleted (soft delete). */
    public function delete(Shipment $shipment, User $actor): void
    {
        if ($shipment->current_status !== ShipmentStatus::CREATED) {
            throw new BusinessRuleException('Only shipments in status CREATED can be deleted.');
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
