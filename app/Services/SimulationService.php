<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;

/**
 * Demo mode. Moves an active shipment one step along its route by writing REAL tracking events through
 * TrackingService (same business rules, notifications and audit log as a human operator).
 * BOOKED -> PICKED_UP -> IN_TRANSIT -> checkpoints ... -> ARRIVED. It stops there: paying and confirming receipt is the customer's job.
 * Events are labelled "[Simulation]".
 */
class SimulationService
{
    public function __construct(private TrackingService $tracking, private RouteGeometry $routes)
    {
    }

    public function advance(Shipment $shipment, User $actor, int $stepPercent): ?TrackingEvent
    {
        $shipment->loadMissing(['origin', 'destination', 'carrier']);
        if (! $shipment->origin || ! $shipment->destination) {
            return null;
        }
        $stepPercent = max(1, min(100, $stepPercent));

        return match ($shipment->current_status) {
            ShipmentStatus::BOOKED => $this->status($shipment, $actor, ShipmentStatus::PICKED_UP, $shipment->origin, 'Cargo picked up at origin.'),
            ShipmentStatus::PICKED_UP => $this->status($shipment, $actor, ShipmentStatus::IN_TRANSIT, $shipment->origin, 'Departed, now in transit.'),
            ShipmentStatus::IN_TRANSIT => $this->move($shipment, $actor, $stepPercent),
            default => null,
        };
    }

    private function move(Shipment $shipment, User $actor, int $stepPercent): TrackingEvent
    {
        $path = $this->routes->path($shipment);
        $cum = [0.0];
        for ($i = 1; $i < count($path); $i++) {
            $cum[] = $cum[$i - 1] + RouteGeometry::distance($path[$i - 1], $path[$i]);
        }
        $total = max(1.0, end($cum));

        $here = ($shipment->current_latitude !== null && $shipment->current_longitude !== null)
            ? [(float) $shipment->current_latitude, (float) $shipment->current_longitude] : $path[0];
        $nearest = 0;
        $best = INF;
        foreach ($path as $i => $p) {
            $d = RouteGeometry::distance($p, $here);
            if ($d < $best) {
                $best = $d;
                $nearest = $i;
            }
        }

        $target = $cum[$nearest] + $total * $stepPercent / 100;
        if ($target >= $total - 1) {
            return $this->status($shipment, $actor, ShipmentStatus::ARRIVED, $shipment->destination, 'Arrived at destination. Waiting for payment and confirmation.');
        }

        [$lat, $lng] = $this->pointAt($path, $cum, $target);

        return $this->tracking->addEvent($shipment, [
            'event_type' => TrackingEvent::TYPE_CHECKPOINT,
            'latitude' => $lat,
            'longitude' => $lng,
            'location_label' => sprintf('En route · %d%%', round($target / $total * 100)),
            'description' => '[Simulation] Position update.',
        ], $actor);
    }

    private function status(Shipment $shipment, User $actor, ShipmentStatus $to, $location, string $text): TrackingEvent
    {
        return $this->tracking->addEvent($shipment, [
            'event_type' => TrackingEvent::TYPE_STATUS_UPDATE,
            'status' => $to->value,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'location_label' => $location->name,
            'description' => '[Simulation] '.$text,
        ], $actor);
    }

    /** @return array{0: float, 1: float} */
    private function pointAt(array $path, array $cum, float $distance): array
    {
        for ($i = 1; $i < count($path); $i++) {
            if ($cum[$i] >= $distance) {
                $f = ($distance - $cum[$i - 1]) / max(1e-9, $cum[$i] - $cum[$i - 1]);

                return [
                    $path[$i - 1][0] + ($path[$i][0] - $path[$i - 1][0]) * $f,
                    $path[$i - 1][1] + ($path[$i][1] - $path[$i - 1][1]) * $f,
                ];
            }
        }

        return end($path);
    }
}
