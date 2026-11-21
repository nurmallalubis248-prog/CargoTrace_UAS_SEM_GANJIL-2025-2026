<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\AuditLog;
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

        $carrierRows = $base()->toBase()
            ->join('carriers', 'carriers.id', '=', 'shipments.carrier_id')
            ->select('carriers.id', 'carriers.name', DB::raw('count(*) as c'))
            ->groupBy('carriers.id', 'carriers.name')
            ->orderByDesc('c')
            ->get();
        $byCarrier = $carrierRows->pluck('c', 'name')->map(fn ($c) => (int) $c)->all();
        $carrierIds = $carrierRows->pluck('id', 'name')->all();

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
                ShipmentStatus::ARRIVED->value,   // arrived but not yet paid + confirmed: stays on the map
                ShipmentStatus::DELIVERED->value, // delivered shipments stay visible on the map
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
                'origin_lat' => $s->origin?->latitude,
                'origin_lng' => $s->origin?->longitude,
                'last_ts' => $s->last_event_at ? $s->last_event_at->getTimestamp() * 1000 : null,
                'eta_ts' => ($s->estimated_arrival_at ?? $s->expected_arrival_at) ? ($s->estimated_arrival_at ?? $s->expected_arrival_at)->getTimestamp() * 1000 : null,
                'delayed' => $s->is_delayed,
                'latitude' => $s->current_latitude,
                'longitude' => $s->current_longitude,
                'label' => $s->current_location_label,
                'updated_at' => $s->last_event_at?->format('Y-m-d H:i').' UTC',
                'url' => route('shipments.show', $s),
            ])->values()->all();

        // Late shipments with the reason taken from their exceptions (customs, documents, weather, ...).
        $delayedList = $base()
            ->where('is_delayed', true)
            ->where('current_status', '!=', ShipmentStatus::CANCELLED->value)
            ->with(['origin', 'destination', 'exceptions'])
            ->orderByDesc('delay_hours')
            ->limit(8)
            ->get()
            ->map(function (Shipment $s) {
                $reason = $s->delayReason();

                return [
                    'number' => $s->shipment_number,
                    'route' => ($s->origin?->name ?? '?').' → '.($s->destination?->name ?? '?'),
                    'delay_hours' => round((float) $s->delay_hours, 1),
                    'severity' => $s->delay_severity,
                    'reason' => $reason['label'] ?? null,
                    'detail' => $reason['description'] ?? null,
                    'url' => route('shipments.show', $s),
                ];
            })->values()->all();

        // ---- Data for the visual dashboard (trend, status ring, latest shipments, top routes, activity) ----
        $cancelled = ShipmentStatus::CANCELLED->value;
        $since = now()->subDays(89)->startOfDay();
        $rows = $base()->where('shipments.created_at', '>=', $since)->get(['shipments.created_at', 'shipments.is_delayed', 'shipments.current_status']);
        $daily = [];
        for ($i = 89; $i >= 0; $i--) {
            $daily[now()->subDays($i)->toDateString()] = ['created' => 0, 'delayed' => 0, 'in_transit' => 0, 'delivered' => 0];
        }
        foreach ($rows as $r) {
            $d = $r->created_at->toDateString();
            if (! isset($daily[$d]) || $r->current_status->value === $cancelled) { continue; }
            $daily[$d]['created']++;
            if ($r->is_delayed) { $daily[$d]['delayed']++; }
            if ($r->current_status === ShipmentStatus::IN_TRANSIT) { $daily[$d]['in_transit']++; }
            if ($r->current_status === ShipmentStatus::DELIVERED) { $daily[$d]['delivered']++; }
        }
        $dailyOut = [];
        foreach ($daily as $date => $v) {
            $dailyOut[] = ['date' => $date, 'created' => $v['created'], 'delayed' => $v['delayed'], 'on_time' => $v['created'] - $v['delayed'], 'in_transit' => $v['in_transit'], 'delivered' => $v['delivered']];
        }
        // Change vs the previous 30 days (null when there is nothing to compare with).
        $cut = now()->subDays(30)->toDateString();
        $change = function (string $key) use ($daily, $cut): ?float {
            $cur = 0; $prev = 0; $prevFrom = now()->subDays(60)->toDateString();
            foreach ($daily as $date => $v) {
                if ($date > $cut) { $cur += $v[$key]; } elseif ($date > $prevFrom) { $prev += $v[$key]; }
            }

            return $prev > 0 ? round(($cur - $prev) / $prev * 100, 0) : null;
        };

        // On time vs delayed ring, overall and per transport mode (air / road / sea).
        $ring = ['ALL' => ['on_time' => 0, 'delayed' => 0]];
        $modeRows = $base()->toBase()
            ->join('carriers', 'carriers.id', '=', 'shipments.carrier_id')
            ->where('shipments.current_status', '!=', $cancelled)
            ->select('carriers.transport_mode', 'shipments.is_delayed', DB::raw('count(*) as c'))
            ->groupBy('carriers.transport_mode', 'shipments.is_delayed')
            ->get();
        foreach ($modeRows as $mr) {
            $mode = $mr->transport_mode ?: 'SEA';
            $key = $mr->is_delayed ? 'delayed' : 'on_time';
            $ring[$mode] ??= ['on_time' => 0, 'delayed' => 0];
            $ring[$mode][$key] += (int) $mr->c;
            $ring['ALL'][$key] += (int) $mr->c;
        }

        $recent = $base()->with(['origin', 'destination'])->orderByDesc('shipments.created_at')->orderByDesc('shipments.id')->limit(5)->get()
            ->map(fn (Shipment $s) => [
                'number' => $s->shipment_number,
                'route' => ($s->origin?->name ?? '?').' → '.($s->destination?->name ?? '?'),
                'status' => $s->current_status->value,
                'delayed' => (bool) $s->is_delayed,
                'eta_ts' => ($s->estimated_arrival_at ?? $s->expected_arrival_at) ? ($s->estimated_arrival_at ?? $s->expected_arrival_at)->getTimestamp() * 1000 : null,
                'url' => route('shipments.show', $s),
            ])->values()->all();

        $topRoutes = $base()->toBase()
            ->join('locations as lo', 'lo.id', '=', 'shipments.origin_id')
            ->join('locations as ld', 'ld.id', '=', 'shipments.destination_id')
            ->select('lo.name as o_name', 'ld.name as d_name', DB::raw('count(*) as c'))
            ->groupBy('shipments.origin_id', 'shipments.destination_id', 'lo.name', 'ld.name')
            ->orderByDesc('c')->limit(3)->get()
            ->map(fn ($r) => ['route' => $r->o_name.' → '.$r->d_name, 'count' => (int) $r->c, 'share_pct' => $total ? round($r->c / $total * 100, 1) : 0.0])
            ->values()->all();

        $activity = [];
        if ($user->isStaff()) {
            $logs = AuditLog::with('actor')->orderByDesc('created_at')->orderByDesc('id')->limit(4)->get();
            $numbers = Shipment::withTrashed()->whereIn('id', $logs->where('entity_type', 'Shipment')->pluck('entity_id')->filter()->all())->pluck('shipment_number', 'id');
            $activity = $logs->map(fn (AuditLog $l) => [
                'ts' => $l->created_at->getTimestamp() * 1000,
                'actor' => $l->actor?->name ?? 'system',
                'action' => $l->action,
                'ref' => $l->entity_type === 'Shipment' ? ($numbers[$l->entity_id] ?? '#'.$l->entity_id) : ($l->changes['code'] ?? '#'.$l->entity_id),
            ])->values()->all();
        }

        $late24 = $base()->where('is_delayed', true)->where('current_status', '!=', $cancelled)->where('delay_hours', '>', 24)->count();

        return [
            'daily' => $dailyOut,
            'change' => ['shipments' => $change('created'), 'in_transit' => $change('in_transit'), 'delivered' => $change('delivered'), 'delayed' => $change('delayed')],
            'ring' => $ring,
            'recent' => $recent,
            'top_routes' => $topRoutes,
            'activity' => $activity,
            'important' => ['late_24h' => $late24],
            'delayed_list' => $delayedList,
            'carrier_ids' => $carrierIds,
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
