<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;

/**
 * Deterministic ETA / delay rules (no AI/ML).
 *
 *  delay_hours = (ETA or actual arrival) - expected arrival
 *  delay <= 0                              => on time
 *  0 < delay <= low_max_hours (default 6)   => LOW
 *  low_max < delay <= medium_max (24)       => MEDIUM
 *  delay > medium_max                       => HIGH
 */
class EtaService
{
    /** Pure classification of an hour difference. Easy to unit test. */
    public function classify(float $delayHours): array
    {
        $lowMax = (float) config('cargotrace.delay.low_max_hours');
        $mediumMax = (float) config('cargotrace.delay.medium_max_hours');

        if ($delayHours <= 0) {
            return ['is_delayed' => false, 'delay_hours' => 0.0, 'severity' => null];
        }

        $severity = match (true) {
            $delayHours <= $lowMax => 'LOW',
            $delayHours <= $mediumMax => 'MEDIUM',
            default => 'HIGH',
        };

        return ['is_delayed' => true, 'delay_hours' => round($delayHours, 2), 'severity' => $severity];
    }

    /** Evaluate a shipment and explain the values used. Does not save anything. */
    public function evaluate(Shipment $shipment): array
    {
        $lowMax = (float) config('cargotrace.delay.low_max_hours');
        $mediumMax = (float) config('cargotrace.delay.medium_max_hours');

        if ($shipment->current_status === ShipmentStatus::CANCELLED) {
            return [
                'is_delayed' => false, 'delay_hours' => 0.0, 'severity' => null,
                'reason' => 'Shipment is cancelled; delay is not evaluated.',
            ];
        }

        $expected = $shipment->expected_arrival_at;
        $delivered = $shipment->current_status === ShipmentStatus::DELIVERED && $shipment->actual_arrival_at;
        $compared = $delivered ? $shipment->actual_arrival_at : ($shipment->estimated_arrival_at ?? $expected);
        $label = $delivered ? 'Actual arrival' : 'Estimated arrival (ETA)';

        $hours = ($compared->getTimestamp() - $expected->getTimestamp()) / 3600;
        $result = $this->classify($hours);

        $result['reason'] = $result['is_delayed']
            ? sprintf(
                '%s %s UTC is %.2f h later than expected arrival %s UTC => %s (thresholds: <= %g h LOW, <= %g h MEDIUM, > %g h HIGH).',
                $label, $compared->format('Y-m-d H:i'), $result['delay_hours'],
                $expected->format('Y-m-d H:i'), $result['severity'], $lowMax, $mediumMax, $mediumMax
            )
            : sprintf(
                '%s %s UTC is not later than expected arrival %s UTC => on time.',
                $label, $compared->format('Y-m-d H:i'), $expected->format('Y-m-d H:i')
            );

        return $result;
    }

    /**
     * Evaluate and persist the delay fields.
     * Returns true when the shipment just became delayed or its severity got worse
     * (callers use this to send a notification).
     */
    public function refresh(Shipment $shipment): bool
    {
        $before = ['delayed' => (bool) $shipment->is_delayed, 'severity' => $shipment->delay_severity];
        $result = $this->evaluate($shipment);

        $shipment->is_delayed = $result['is_delayed'];
        $shipment->delay_hours = $result['delay_hours'];
        $shipment->delay_severity = $result['severity'];
        $shipment->save();

        $rank = ['LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3];

        return $result['is_delayed'] && (
            ! $before['delayed'] || ($rank[$result['severity'] ?? ''] ?? 0) > ($rank[$before['severity'] ?? ''] ?? 0)
        );
    }
}
