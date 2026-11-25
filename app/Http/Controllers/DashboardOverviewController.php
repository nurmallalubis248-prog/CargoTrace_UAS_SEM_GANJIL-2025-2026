<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Data kartu/grafik dashboard. SEMUA angka diambil dari tabel di cargotrace_schema.sql
 * (shipments, locations, carriers, shipment_exceptions, payments, notifications, audit_logs).
 * Tidak ada data tambahan/rekaan. Pelanggan (customer) hanya melihat miliknya sendiri.
 */
class DashboardOverviewController extends Controller
{
    /** Kode lokasi CargoTrace -> kode ICAO untuk METAR. Kode 4 huruf di tabel locations dipakai apa adanya. */
    private const ICAO = ['SGSIA' => 'WSSS', 'AEDXB' => 'OMDB', 'IDCGK' => 'WIII'];

    private const STATUS_LABEL = [
        'CREATED' => 'Dibuat', 'BOOKED' => 'Dipesan', 'PICKED_UP' => 'Diambil',
        'IN_TRANSIT' => 'Dalam Perjalanan', 'ARRIVED' => 'Tiba', 'DELIVERED' => 'Terkirim',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $isCustomer = $user->role === 'customer';
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $carrierId = $request->query('carrier_id') ? (int) $request->query('carrier_id') : null;
        $today = Carbon::now('UTC')->startOfDay();

        // ---- shipments (tidak termasuk yang soft-deleted) + nama lokasi asal/tujuan
        $q = DB::table('shipments as s')
            ->join('locations as o', 'o.id', '=', 's.origin_id')
            ->join('locations as d', 'd.id', '=', 's.destination_id')
            ->whereNull('s.deleted_at')
            ->select('s.id', 's.shipment_number', 's.carrier_id', 's.current_status', 's.is_delayed', 's.delay_hours',
                's.created_at', 's.expected_arrival_at', 's.estimated_arrival_at', 's.actual_arrival_at',
                'o.name as origin', 'd.name as destination');
        if ($isCustomer) { $q->where('s.customer_id', $user->id); }
        $all = $q->orderByDesc('s.created_at')->orderByDesc('s.id')->get();
        $ships = $carrierId ? $all->where('carrier_id', $carrierId)->values() : $all;
        $total = $ships->count();

        // ---- KPI + perubahan 30 hari terakhir vs 30 hari sebelumnya (berdasarkan tanggal dibuat)
        $inWin = function ($rows, int $from, int $to) {
            return $rows->filter(function ($r) use ($from, $to) {
                $a = Carbon::parse($r->created_at, 'UTC')->diffInDays(Carbon::now('UTC'), false);
                return $a >= $from && $a < $to;
            })->count();
        };
        $kpiRows = [
            'total'     => $all,
            'transit'   => $all->where('current_status', 'IN_TRANSIT'),
            'delivered' => $all->where('current_status', 'DELIVERED'),
            'delayed'   => $all->where('is_delayed', 1),
        ];
        $kpi = [];
        foreach ($kpiRows as $k => $rows) {
            $cur = $inWin($rows, 0, 30); $prev = $inWin($rows, 30, 60);
            $kpi[$k] = ['value' => $rows->count(), 'delta_pct' => $prev > 0 ? round(($cur - $prev) / $prev * 100) : null];
        }

        // ---- tren harian (Ringkasan Kinerja + Grafik per Status)
        $labels = []; $perDay = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $labels[] = $d->locale('id')->translatedFormat('d M');
            $perDay[$d->format('Y-m-d')] = ['all' => 0, 'late' => 0];
        }
        foreach ($ships as $s) {
            $key = Carbon::parse($s->created_at, 'UTC')->format('Y-m-d');
            if (isset($perDay[$key])) { $perDay[$key]['all']++; $perDay[$key]['late'] += (int) $s->is_delayed; }
        }
        $trend = [
            'labels'    => $labels,
            'shipments' => array_column(array_values($perDay), 'all'),
            'delayed'   => array_column(array_values($perDay), 'late'),
            'on_time'   => array_map(fn ($v) => $v['all'] - $v['late'], array_values($perDay)),
        ];

        foreach ($kpiRows as $k => $rows) {   // garis kecil di kartu KPI: jumlah per hari (tanggal dibuat)
            $spark = array_fill_keys(array_keys($perDay), 0);
            foreach ($rows as $r) {
                $key = Carbon::parse($r->created_at, 'UTC')->format('Y-m-d');
                if (isset($spark[$key])) { $spark[$key]++; }
            }
            $kpi[$k]['spark'] = array_values($spark);
        }

        // ---- donut Tepat Waktu vs Terlambat
        $late = $ships->where('is_delayed', 1)->count();
        $ok = $total - $late;
        $donut = ['total' => $total, 'on_time' => $ok, 'late' => $late,
            'pct_on_time' => $total ? round($ok / $total * 100, 1) : null,
            'pct_late'    => $total ? round($late / $total * 100, 1) : null];

        // ---- Info Penting
        $openExc = DB::table('shipment_exceptions as e')->join('shipments as s', 's.id', '=', 'e.shipment_id')
            ->whereNull('s.deleted_at')->where('e.status', '!=', 'RESOLVED')
            ->when($isCustomer, fn ($qq) => $qq->where('s.customer_id', $user->id))->count();
        $pendPay = DB::table('payments')->where('status', 'PAID')->where('payout_status', 'PENDING_SETTLEMENT')
            ->when($isCustomer, fn ($qq) => $qq->where('user_id', $user->id))->count();
        $info = [
            ['key' => 'late24',   'count' => $all->where('is_delayed', 1)->filter(fn ($r) => (float) $r->delay_hours > 24)->count()],
            ['key' => 'exception','count' => $openExc],
            ['key' => 'payout',   'count' => $pendPay],
        ];

        // ---- Pengiriman Terbaru (5 terakhir dibuat)
        $recent = $ships->take(5)->map(function ($r) {
            $eta = $r->actual_arrival_at ?: ($r->estimated_arrival_at ?: $r->expected_arrival_at);
            if ($r->is_delayed) { $tone = 'late'; $label = 'Terlambat'; }
            elseif ($r->current_status === 'IN_TRANSIT') { $tone = 'blue'; $label = 'Dalam Perjalanan'; }
            elseif ($r->current_status === 'DELIVERED') { $tone = 'ok'; $label = 'Tepat Waktu'; }
            else { $tone = 'gray'; $label = self::STATUS_LABEL[$r->current_status] ?? $r->current_status; }
            return ['url' => route('shipments.show', $r->id), 'number' => $r->shipment_number,
                'route' => $r->origin.' → '.$r->destination, 'tone' => $tone, 'label' => $label,
                'eta' => Carbon::parse($eta, 'UTC')->locale('id')->translatedFormat('d M')];
        })->values();

        // ---- Top Rute (3 terbanyak, % dari total pengiriman)
        $topRoutes = $ships->groupBy(fn ($r) => $r->origin.' → '.$r->destination)
            ->map(fn ($g, $route) => ['route' => $route, 'count' => $g->count(), 'pct' => $total ? round($g->count() / $total * 100, 1) : 0])
            ->sortBy([['count', 'desc'], ['route', 'asc']])->take(3)->values();

        // ---- Aktivitas Sistem (3 audit log terbaru)
        $logs = DB::table('audit_logs')->orderByDesc('created_at')->orderByDesc('id')->limit(40)->get();
        $numById = $all->keyBy('id');
        $excShip = DB::table('shipment_exceptions')->pluck('shipment_id', 'id');
        $excType = DB::table('shipment_exceptions')->pluck('type', 'id');
        $activity = [];
        foreach ($logs as $l) {
            $sid = $l->entity_type === 'Shipment' ? $l->entity_id : ($excShip[$l->entity_id] ?? null);
            if ($isCustomer && ! isset($numById[$sid])) { continue; }
            $no = $numById[$sid]->shipment_number ?? ('#'.$sid);
            $c = json_decode($l->changes ?? '[]', true) ?: [];
            $kind = 'tracking';
            switch ($l->action) {
                case 'shipment.created':  $kind = 'created'; $text = "Pengiriman $no dibuat"; break;
                case 'tracking.added':    $text = "Pelacakan $no diperbarui".(! empty($c['status']) ? ' ('.(self::STATUS_LABEL[$c['status']] ?? $c['status']).')' : ''); break;
                case 'exception.opened':  $kind = 'exception'; $text = 'Exception '.($excType[$l->entity_id] ?? '').' dibuka pada '.$no; break;
                case 'exception.updated': $kind = 'exception'; $text = 'Exception '.($excType[$l->entity_id] ?? '')." pada $no diperbarui"; break;
                default:                  $text = $l->action.' '.$l->entity_type;
            }
            $at = Carbon::parse($l->created_at, 'UTC');
            $activity[] = ['kind' => $kind, 'time' => $at->format('H:i'), 'at' => $at->toDateTimeString().' UTC', 'text' => $text];
            if (count($activity) === 3) { break; }
        }

        return response()->json([
            'days' => $days,
            'carriers' => DB::table('carriers')->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
            'kpi' => $kpi, 'trend' => $trend, 'donut' => $donut, 'info' => $info,
            'recent' => $recent, 'top_routes' => $topRoutes, 'activity' => $activity,
            'unread' => DB::table('notifications')->where('user_id', $user->id)->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Daftar lokasi aktif (pelabuhan, bandara, gudang) + jumlah pengiriman aktif dari tabel SQL.
     * Cuaca/laut diambil browser dari Open-Meteo menurut koordinat lokasi.
     */
    public function locations(Request $request): JsonResponse
    {
        $user = $request->user();
        $isCustomer = $user->role === 'customer';

        $ships = DB::table('shipments')->whereNull('deleted_at')->where('current_status', '!=', 'DELIVERED')
            ->when($isCustomer, fn ($q) => $q->where('customer_id', $user->id))
            ->get(['id', 'origin_id', 'destination_id', 'is_delayed']);
        $openShip = DB::table('shipment_exceptions')->where('status', '!=', 'RESOLVED')->pluck('shipment_id')->flip();

        $rows = DB::table('locations')->where('is_active', 1)->orderBy('type')->orderBy('name')
            ->get(['id', 'name', 'code', 'country', 'type', 'latitude', 'longitude'])
            ->map(function ($l) use ($ships, $openShip) {
                $in = $ships->where('destination_id', $l->id);
                $out = $ships->where('origin_id', $l->id);
                $all = $in->merge($out)->unique('id');
                return [
                    'id' => $l->id, 'name' => $l->name, 'code' => $l->code, 'country' => $l->country, 'type' => $l->type,
                    'latitude' => (float) $l->latitude, 'longitude' => (float) $l->longitude,
                    'active_in' => $in->count(), 'active_out' => $out->count(),
                    'delayed' => $all->where('is_delayed', 1)->count(),
                    'open_exceptions' => $all->filter(fn ($s) => isset($openShip[$s->id]))->count(),
                ];
            })->values();

        return response()->json($rows);
    }

    /** METAR resmi bandara (NOAA Aviation Weather Center, tanpa API key). Di-cache 10 menit. */
    public function metar(Request $request): JsonResponse
    {
        $map = [];   // icao => location id
        foreach (DB::table('locations')->where('is_active', 1)->where('type', 'AIRPORT')->get(['id', 'code']) as $a) {
            $icao = self::ICAO[$a->code] ?? (preg_match('/^[A-Z]{4}$/', $a->code) ? $a->code : null);
            if ($icao) { $map[$icao] = $a->id; }
        }
        if (! $map) { return response()->json((object) []); }

        $key = 'metar:'.md5(implode(',', array_keys($map)));
        $rows = Cache::get($key);
        if ($rows === null) {
            try {
                $r = Http::timeout(8)->withHeaders(['User-Agent' => 'CargoTrace/1.0 (student project)'])
                    ->get('https://aviationweather.gov/api/data/metar', ['ids' => implode(',', array_keys($map)), 'format' => 'json']);
                $rows = $r->successful() ? ($r->json() ?: []) : [];
            } catch (\Throwable $e) { $rows = []; }
            if ($rows) { Cache::put($key, $rows, 600); }
        }

        $out = [];
        foreach ($rows as $m) {
            $icao = $m['icaoId'] ?? null;
            if (! $icao || ! isset($map[$icao])) { continue; }
            $out[$map[$icao]] = [
                'icao' => $icao, 'raw' => $m['rawOb'] ?? null, 'cat' => $m['fltCat'] ?? null,
                'temp' => $m['temp'] ?? null,
                'wind_kmh' => isset($m['wspd']) ? round($m['wspd'] * 1.852) : null,
                'gust_kmh' => isset($m['wgst']) ? round($m['wgst'] * 1.852) : null,
                'visib' => $m['visib'] ?? null,   // statute miles (mis. "10+")
                'wx' => $m['wxString'] ?? null,
            ];
        }
        return response()->json((object) $out);
    }
}
