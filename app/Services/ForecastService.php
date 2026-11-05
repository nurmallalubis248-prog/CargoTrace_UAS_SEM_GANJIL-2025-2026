<?php

namespace App\Services;

use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Delay forecasting (statistical model, no external AI service):
 *  predicted delay = max(current delay, 0.6 x average delay of the same carrier+lane) + penalty of open exceptions.
 * Confidence grows with the number of historical shipments on the lane.
 */
class ForecastService
{
    private const PENALTY = ['LOW' => 2.0, 'MEDIUM' => 6.0, 'HIGH' => 18.0, 'CRITICAL' => 36.0];

    public function forecast(Builder $base, int $limit = 8): array
    {
        $active = (clone $base)->whereIn('current_status', ['BOOKED', 'PICKED_UP', 'IN_TRANSIT'])
            ->with(['origin', 'destination', 'carrier', 'exceptions' => fn ($q) => $q->open()])->get();

        $rows = [];
        foreach ($active as $s) {
            $lane = Shipment::where('carrier_id', $s->carrier_id)->where('origin_id', $s->origin_id)
                ->where('destination_id', $s->destination_id)->where('id', '!=', $s->id)
                ->where('current_status', '!=', 'CANCELLED')->pluck('delay_hours');
            $avg = $lane->count() ? (float) $lane->avg() : 0.0;
            $penalty = (float) $s->exceptions->sum(fn ($e) => self::PENALTY[$e->severity->value ?? $e->severity] ?? 0);
            $predicted = round(max((float) $s->delay_hours, 0.6 * $avg) + $penalty, 1);
            $risk = $predicted <= 0.5 ? 'ON_TIME' : ($predicted <= 6 ? 'LOW' : ($predicted <= 24 ? 'MEDIUM' : 'HIGH'));

            $rows[] = [
                'number' => $s->shipment_number,
                'url' => route('shipments.show', $s),
                'route' => ($s->origin?->code ?? '?').' → '.($s->destination?->code ?? '?'),
                'mode' => $s->carrier->transport_mode ?? 'SEA',
                'predicted_delay_hours' => $predicted,
                'predicted_eta' => $s->expected_arrival_at?->copy()->addMinutes((int) ($predicted * 60))->format('Y-m-d H:i'),
                'risk' => $risk,
                'confidence' => (int) min(95, 45 + 10 * $lane->count()),
            ];
        }

        usort($rows, fn ($a, $b) => $b['predicted_delay_hours'] <=> $a['predicted_delay_hours']);

        return array_slice($rows, 0, $limit);
    }
}
