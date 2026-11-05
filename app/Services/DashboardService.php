<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** All dashboard numbers come from the database (no static values). Customers only see their own data. */
class DashboardService
{
    public function stats(User $user): array
    {
        $base = fn () => Shipment::query()->visibleTo($user);

        $total = $base()->count();
        $byStatus = $base()->toBase()
            ->select('shipments.current_status', DB::raw('count(*) as c'))
            ->groupBy('shipments.current_status')
            ->pluck('c', 'current_status')
            ->map(fn ($c) => (int) $c)
            ->all();

        $byCarrier = $base()->toBase()
            ->join('carriers', 'carriers.id', '=', 'shipments.carrier_id')
            ->select('carriers.name', DB::raw('count(*) as c'))
            ->groupBy('carriers.name')
            ->orderByDesc('c')
            ->pluck('c', 'name')
            ->map(fn ($c) => (int) $c)
            ->all();

        $bySeverity = $base()->where('is_delayed', true)->toBase()
            ->select('shipments.delay_severity', DB::raw('count(*) as c'))
            ->groupBy('shipments.delay_severity')
            ->pluck('c', 'delay_severity')
            ->map(fn ($c) => (int) $c)
            ->all();

        $notCancelled = $base()->where('current_status', '!=', ShipmentStatus::CANCELLED->value)->count();
        $delayed = $base()->where('is_delayed', true)->where('current_status', '!=', ShipmentStatus::CANCELLED->value)->count();
        $delivered = (int) ($byStatus[ShipmentStatus::DELIVERED->value] ?? 0);
        $deliveredOnTime = $base()->where('current_status', ShipmentStatus::DELIVERED->value)->where('is_delayed', false)->count();
        $withException = $base()->has('exceptions')->count();

        $visibleExceptions = fn () => ShipmentException::query()
            ->whereHas('shipment', fn ($q) => $q->visibleTo($user));

        $openExceptions = $visibleExceptions()->open()->count();

        $resolved = $visibleExceptions()->whereNotNull('resolved_at')->get(['detected_at', 'resolved_at']);
        $avgResolution = $resolved->isEmpty() ? null : round(
            $resolved->avg(fn ($e) => ($e->resolved_at->getTimestamp() - $e->detected_at->getTimestamp()) / 3600), 1
        );

        $avgDelay = $base()->where('is_delayed', true)->avg('delay_hours');

        $positions = $base()
            ->whereIn('current_status', [
                ShipmentStatus::BOOKED->value, ShipmentStatus::PICKED_UP->value, ShipmentStatus::IN_TRANSIT->value,
            ])
            ->whereNotNull('current_latitude')
            ->orderByDesc('last_event_at')
            ->with(['carrier', 'destination', 'origin'])
            ->limit(200)
            ->get()
            ->map(fn (Shipment $s) => [
                'id' => $s->id,
                'shipment_number' => $s->shipment_number,
                'status' => $s->current_status->value,
                'mode' => $s->carrier->transport_mode ?? 'SEA',
                'origin_country' => $s->origin?->country,
                'dest_country' => $s->destination?->country,
                'dest_lat' => $s->destination?->latitude,
                'dest_lng' => $s->destination?->longitude,
                'delayed' => $s->is_delayed,
                'latitude' => $s->current_latitude,
                'longitude' => $s->current_longitude,
                'label' => $s->current_location_label,
                'updated_at' => $s->last_event_at?->format('Y-m-d H:i').' UTC',
                'url' => route('shipments.show', $s),
            ])->values()->all();

        return [
            'forecast' => app(ForecastService::class)->forecast($base()),
            'payments' => [
                'paid_amount' => (int) \App\Models\Payment::where('status', 'PAID')->whereIn('shipment_id', $base()->select('shipments.id'))->sum('amount'),
                'pending' => \App\Models\Payment::where('status', 'PENDING')->whereIn('shipment_id', $base()->select('shipments.id'))->count(),
            ],
            'totals' => [
                'shipments' => $total,
                'in_transit' => (int) ($byStatus[ShipmentStatus::IN_TRANSIT->value] ?? 0),
                'delivered' => $delivered,
                'delayed' => $delayed,
                'open_exceptions' => $openExceptions,
            ],
            'rates' => [
                'delay_rate_pct' => $notCancelled ? round($delayed / $notCancelled * 100, 1) : 0.0,
                'exception_rate_pct' => $total ? round($withException / $total * 100, 1) : 0.0,
                'on_time_delivery_rate_pct' => $delivered ? round($deliveredOnTime / $delivered * 100, 1) : null,
                'avg_delay_hours' => $avgDelay !== null ? round((float) $avgDelay, 1) : null,
                'avg_exception_resolution_hours' => $avgResolution,
            ],
            'by_status' => $byStatus,
            'by_carrier' => $byCarrier,
            'delay_by_severity' => $bySeverity,
            'positions' => $positions,
        ];
    }
}
