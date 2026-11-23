@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $cardCls = 'card block transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-sakura-300';
    $ico = [
        'box'   => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'plane' => 'M21 3 3 10.5l6.5 2.5L12 20l2.5-5.5L21 3Zm-11.5 10L21 3',
        'check' => 'm5 12.5 4.5 4.5L19 7.5',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v5l3 2',
    ];
    // [kunci data, label, ikon, warna, link]
    $kpi = [
        ['total',     'Total Pengiriman', 'box',   'kpi-pink',  route('shipments.index')],
        ['transit',   'Dalam Perjalanan', 'plane', 'kpi-green', route('shipments.index', ['status' => 'IN_TRANSIT'])],
        ['delivered', 'Terkirim',         'check', 'kpi-pink',  route('shipments.index', ['status' => 'DELIVERED'])],
        ['delayed',   'Terlambat',        'clock', 'kpi-green', route('shipments.index', ['delayed' => 1])],
    ];
@endphp

<style>
    .kpi-card { position: relative; overflow: hidden; }
    .kpi-card.t-pink  { background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(255,225,240,.85)) !important; }
    .kpi-card.t-green { background: linear-gradient(135deg, rgba(255,255,255,.9), rgba(228,246,205,.85)) !important; }
    .kpi-ico { display: grid; place-items: center; width: 3.6rem; height: 3.6rem; border-radius: 9999px; color: #fff; flex: none; box-shadow: 0 6px 16px -4px rgba(0,0,0,.25); }
    .kpi-pink  { background: linear-gradient(135deg, #FF7DB5, #F0407F); }
    .kpi-green { background: linear-gradient(135deg, #A8DB6A, #6DB43A); }
    .kpi-spark { position: absolute; right: .9rem; bottom: .7rem; width: 48%; height: 55%; pointer-events: none; }
    .delta-up { color: #4C9A1F; } .delta-down { color: #D6317F; }
    .pill { border-radius: 9999px; padding: .15rem .6rem; font-size: .68rem; font-weight: 700; white-space: nowrap; }
    .pill-ok { background: #E3F4CC; color: #4C8A1F; } .pill-late { background: #FFE0EE; color: #D6317F; }
    .pill-blue { background: #DCEBFF; color: #2F6FD0; } .pill-gray { background: #EEF0EA; color: #565E4E; }
    .mode-btn { background: #fff; border: 1px solid #F6D3E3; border-radius: 9999px; padding: .25rem .8rem; cursor: pointer; transition: all .15s; }
    .mode-btn.on { background: linear-gradient(90deg, #FF6FAE, #F0407F); color: #fff; border-color: transparent; box-shadow: 0 4px 12px rgba(240,64,127,.3); }
    .sel { border-radius: 9999px; background: #fff; border: 1px solid #F6D3E3; padding: .25rem 1.8rem .25rem .8rem; font-size: .75rem; font-weight: 700; color: #2B3027; cursor: pointer; }
    .section-title { display: flex; align-items: center; gap: .55rem; font-weight: 800; color: #2B3027; }
    .section-title svg { width: 1.3rem; height: 1.3rem; color: #E91E8C; flex: none; }
    .mini-link { font-size: .75rem; font-weight: 700; color: #D6317F; white-space: nowrap; }
    .mini-link:hover { text-decoration: underline; }
    .act-ico { display: grid; place-items: center; width: 1.7rem; height: 1.7rem; border-radius: .5rem; flex: none; }
    .act-created { background: #FFE0EE; color: #E91E8C; } .act-tracking { background: #DCEBFF; color: #2F6FD0; } .act-exception { background: #FFF0D2; color: #D98A00; }
    .lyr { display: inline-flex; align-items: center; gap: .35rem; background: #fff; border: 1px solid #F6D3E3; border-radius: 9999px; padding: .25rem .75rem; cursor: pointer; user-select: none; }
    .lyr input { accent-color: #E91E8C; }
    .cond-chip { display: inline-flex; align-items: center; gap: .35rem; border-radius: 9999px; background: #fff; border: 1px solid #F6D3E3; padding: .2rem .65rem; font-weight: 700; }
    .cond-pin { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 9999px; background: #fff; border: 3px solid #3FAE49; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,.3); }
    .cond-pin.lv1 { border-color: #F5A524; } .cond-pin.lv2 { border-color: #E5484D; animation: condPulse 1.6s infinite; }
    @keyframes condPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(229,72,77,.55); } 50% { box-shadow: 0 0 0 8px rgba(229,72,77,0); } }
    .plane-pin svg { width: 18px; height: 18px; filter: drop-shadow(0 1px 1px rgba(0,0,0,.4)); }
    #map-legend { position: absolute; left: .6rem; bottom: 1.6rem; z-index: 500; background: rgba(255,255,255,.95); border-radius: .75rem; padding: .45rem .7rem; font-size: .7rem; font-weight: 700; box-shadow: 0 4px 14px rgba(0,0,0,.18); line-height: 1.5; }
    #map-legend i { display: inline-block; width: .7rem; height: .7rem; border-radius: 9999px; margin-right: .35rem; vertical-align: -1px; }
    .cond-pop b { color: #2B3027; } .cond-pop table td { padding: 1px 8px 1px 0; vertical-align: top; }
    .dash-deco { position: absolute; pointer-events: none; z-index: 0; }
    /* Peta + tombol layar penuh */
    #map-wrap { position: relative; }
    #map-wrap:fullscreen, #map-wrap.map-fs-fallback { display: flex; flex-direction: column; background: #FFF6FA; padding: 1rem; }
    #map-wrap.map-fs-fallback { position: fixed; inset: 0; z-index: 2000; }
    #map-wrap:fullscreen #map-box, #map-wrap.map-fs-fallback #map-box { flex: 1; min-height: 0; }
    #map-wrap:fullscreen #map, #map-wrap.map-fs-fallback #map { height: 100% !important; border-radius: 16px; }
    #map-fs { position: absolute; top: .6rem; right: .6rem; z-index: 500; display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .75rem; background: #fff; color: #D6317F; box-shadow: 0 4px 14px rgba(0,0,0,.2); cursor: pointer; transition: transform .15s; }
    #map-fs:hover { transform: scale(1.06); background: #FFF0F7; }
    #map-fs .ico-exit { display: none; }
    #map-wrap.is-fs #map-fs .ico-enter { display: none; } #map-wrap.is-fs #map-fs .ico-exit { display: block; }
</style>

<div class="relative">
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-900 sm:text-3xl">Selamat datang kembali, {{ auth()->user()->name }}!</h1>
        <p class="text-sm text-ink-600">Pantau pengiriman Anda dengan mudah dan real-time. 🌿</p>
    </div>

    <div id="dash-error" class="mb-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        Tidak dapat memuat data dashboard. Silakan muat ulang halaman.
    </div>

    {{-- Baris 1: KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpi as [$key, $label, $icon, $tone, $href])
            <a href="{{ $href }}" class="{{ $cardCls }} kpi-card {{ $tone === 'kpi-pink' ? 't-pink' : 't-green' }}">
                <div class="flex items-center gap-4">
                    <span class="kpi-ico {{ $tone }}"><svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ico[$icon] }}"/></svg></span>
                    <div>
                        <p class="text-sm font-bold text-ink-800">{{ $label }}</p>
                        <p class="flex items-baseline gap-2"><span id="kpi-{{ $key }}" class="text-4xl font-extrabold leading-tight text-ink-900">&ndash;</span><span id="kpi-{{ $key }}-d" class="text-xs font-bold" title="Dibanding 30 hari sebelumnya"></span></p>
                    </div>
                </div>
                <svg class="kpi-spark" id="spark-{{ $key }}" data-tone="{{ $tone }}" viewBox="0 0 100 40" preserveAspectRatio="none"></svg>
            </a>
        @endforeach
    </div>

    {{-- Baris 2: Ringkasan Kinerja | Status Pengiriman | Info Penting --}}
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="card min-w-0 xl:col-span-6">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 class="section-title"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 20V10m7 10V4m7 16v-7"/></svg>Ringkasan Kinerja</h2>
                <select class="sel days-sel" aria-label="Rentang waktu"><option value="30">30 Hari Terakhir</option><option value="7">7 Hari Terakhir</option><option value="90">90 Hari Terakhir</option></select>
            </div>
            <div class="relative h-60"><canvas id="chart-perf"></canvas></div>
        </div>
        <div class="card min-w-0 xl:col-span-3">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="section-title whitespace-nowrap"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 3v9h9a9 9 0 1 1-9-9Z"/></svg>Status Pengiriman</h2>
                <select id="carrier-sel" class="sel max-w-full" aria-label="Pengangkut"><option value="">Semua</option></select>
            </div>
            <div class="flex flex-col items-center gap-4">
                <div class="relative h-40 w-40 flex-none">
                    <canvas id="chart-donut"></canvas>
                    <div class="pointer-events-none absolute inset-0 grid place-items-center text-center"><div><p id="donut-pct" class="text-xl font-extrabold text-ink-900">–</p><p class="text-[11px] font-semibold leading-tight text-ink-600">Tepat Waktu</p></div></div>
                </div>
                <div class="grid w-full grid-cols-2 gap-3 text-xs">
                    <div class="flex min-w-0 items-start gap-2"><span class="mt-1 h-2.5 w-2.5 flex-none rounded-full" style="background:#FF6FAE"></span><span class="min-w-0">Tepat Waktu<b id="lg-ok" class="block text-sm">–</b></span></div>
                    <div class="flex min-w-0 items-start gap-2"><span class="mt-1 h-2.5 w-2.5 flex-none rounded-full" style="background:#8CCB4F"></span><span class="min-w-0">Terlambat<b id="lg-late" class="block text-sm">–</b></span></div>
                </div>
            </div>
        </div>
        <div class="card min-w-0 xl:col-span-3">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="section-title"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/></svg>Info Penting</h2>
                <a href="{{ route('notifications.index') }}" class="mini-link">Lihat Semua →</a>
            </div>
            <ul id="info-list" class="space-y-2 text-sm"></ul>
        </div>
    </div>

    {{-- Baris 3: Peta | Pengiriman Terbaru --}}
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div id="map-wrap" class="card xl:col-span-7">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="section-title"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg>Peta Pengiriman</h2>
                <div id="mode-filter" class="flex flex-wrap items-center gap-2 text-xs font-bold text-ink-700">
                    <button type="button" data-mode="" class="mode-btn">Semua</button>
                    <button type="button" data-mode="AIR" class="mode-btn">✈ Pesawat</button>
                    <button type="button" data-mode="ROAD" class="mode-btn">🚚 Truk</button>
                    <button type="button" data-mode="SEA" class="mode-btn">🚢 Kapal</button>
                    <select id="region-sel" class="sel" aria-label="Wilayah peta"><option value="world">Global</option><option value="asia">Asia</option><option value="europe">Eropa</option><option value="america">Amerika</option><option value="oceania">Oseania</option></select>
                </div>
            </div>
            <div id="layer-toggles" class="mb-2 flex flex-wrap items-center gap-2 text-xs font-bold text-ink-700">
                <span class="text-ink-500">Lapisan:</span>
                <label class="lyr"><input type="checkbox" data-lyr="PORT" checked> ⚓ Pelabuhan</label>
                <label class="lyr"><input type="checkbox" data-lyr="AIRPORT" checked> ✈ Bandara</label>
                <label class="lyr"><input type="checkbox" data-lyr="WAREHOUSE"> 🏭 Gudang</label>
                <label class="lyr"><input type="checkbox" id="lyr-radar"> 🌧 Radar cuaca</label>
                <label class="lyr"><input type="checkbox" id="lyr-sea"> ⚓ Peta laut</label>
                <label class="lyr"><input type="checkbox" id="lyr-planes"> 🛩 Pesawat live</label>
                <button type="button" id="cond-refresh" class="lyr" title="Muat ulang kondisi">⟳ Segarkan</button>
            </div>
            <div id="cond-summary" class="mb-2 flex flex-wrap items-center gap-2 text-xs"></div>
            <p id="map-empty" class="mb-2 hidden text-sm text-ink-500">Belum ada posisi pengiriman.</p>
            <p id="map-filter-empty" class="mb-2 hidden text-sm text-ink-500">Tidak ada pengiriman pada jalur ini.</p>
            <div id="map-box" class="relative">
                <div id="map" class="h-[26rem] w-full rounded-2xl" data-no-i18n></div>
                <div id="map-legend" aria-label="Legenda kondisi"><div><i style="background:#3FAE49"></i>Normal</div><div><i style="background:#F5A524"></i>Waspada (cuaca, METAR MVFR, kapal antre)</div><div><i style="background:#E5484D"></i>Gangguan (badai, METAR IFR, antrean padat)</div></div>
                {{-- Tombol Perluas / Layar Penuh --}}
                <button id="map-fs" type="button" title="Layar penuh" aria-label="Layar penuh" aria-pressed="false">
                    <svg class="ico-enter h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7"/></svg>
                    <svg class="ico-exit h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 10h-6V4M4 14h6v6M14 10l7-7M10 14l-7 7"/></svg>
                </button>
            </div>
        </div>
        <div class="card xl:col-span-5">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="section-title"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>Pengiriman Terbaru</h2>
                <a href="{{ route('shipments.index') }}" class="mini-link">Lihat Semua →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-xs text-ink-500"><th class="th !bg-transparent">No. Resi</th><th class="th !bg-transparent">Rute</th><th class="th !bg-transparent">Status</th><th class="th !bg-transparent">ETA</th></tr></thead>
                    <tbody id="recent-body" class="divide-y divide-pink-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Baris 4: Grafik per Status | Top Rute | Aktivitas Sistem --}}
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="card xl:col-span-4">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 class="section-title">Grafik Pengiriman per Status</h2>
                <select class="sel days-sel" aria-label="Rentang waktu"><option value="30">30 Hari Terakhir</option><option value="7">7 Hari Terakhir</option><option value="90">90 Hari Terakhir</option></select>
            </div>
            <div class="mb-1 flex gap-4 text-xs"><span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-full" style="background:#8CCB4F"></i>Tepat Waktu</span><span class="flex items-center gap-1.5"><i class="h-2.5 w-2.5 rounded-full" style="background:#FF6FAE"></i>Terlambat</span></div>
            <div class="relative h-44"><canvas id="chart-status"></canvas></div>
        </div>
        <div class="card xl:col-span-4">
            <div class="mb-3 flex items-center justify-between"><h2 class="section-title">Top Rute</h2><a href="{{ route('reports.index') }}" class="mini-link">Lihat Semua →</a></div>
            <ol id="top-routes" class="space-y-4 text-sm"></ol>
        </div>
        <div class="card relative overflow-hidden xl:col-span-4">
            <div class="mb-3 flex items-center justify-between"><h2 class="section-title">Aktivitas Sistem</h2><span class="flex items-center gap-1 text-xs font-bold text-green-600"><span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>Live</span></div>
            <ul id="activity" class="relative z-10 space-y-3 pb-14 text-sm"></ul>
            {{-- hiasan kapal kargo (seperti desain) --}}
            <svg class="dash-deco" style="right:-.5rem;bottom:-.25rem;width:11rem" viewBox="0 0 220 110" aria-hidden="true">
                <path d="M10 70 H205 L190 98 H34 Z" fill="#5B3A47"/><path d="M10 70 H205 L200 78 H14 Z" fill="#8E5A6E"/>
                <rect x="30" y="46" width="30" height="22" fill="#F0407F"/><rect x="60" y="46" width="30" height="22" fill="#FF9CC6"/><rect x="90" y="46" width="30" height="22" fill="#8CCB4F"/><rect x="120" y="46" width="30" height="22" fill="#F0407F"/>
                <rect x="45" y="26" width="30" height="20" fill="#FF9CC6"/><rect x="75" y="26" width="30" height="20" fill="#F0407F"/><rect x="105" y="26" width="30" height="20" fill="#B7E27F"/>
                <rect x="160" y="30" width="28" height="40" fill="#FFE3F0"/><rect x="166" y="34" width="16" height="7" fill="#7C9BD0"/>
                <path d="M0 104 C40 96 80 108 120 102 C160 96 190 106 220 100 V110 H0Z" fill="#B7E27F" opacity=".85"/>
            </svg>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/ct-map.js') }}?v={{ filemtime(public_path('js/ct-map.js')) }}"></script>
<script src="{{ asset('js/ct-live.js') }}?v={{ filemtime(public_path('js/ct-live.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const PINK = '#FF5FA2', GREEN = '#8CCB4F';
    const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const SHIP_URL = '{{ route('shipments.index') }}';
    const URLS = { late24: '{{ route('shipments.index', ['delayed' => 1]) }}', exception: '{{ route('shipments.index', ['exception' => 'open']) }}', payout: '{{ auth()->user()->isAdmin() ? route('admin.accounting.index') : route('reports.index') }}' };

    /* ================= PETA ================= */
    const map = CT.baseMap('map', [15, 80], 3);
    const markers = L.layerGroup().addTo(map);
    const overviewLayer = L.layerGroup().addTo(map);
    const routeLayer = L.layerGroup().addTo(map);
    let specs = {}, staticMk = {}, activeId = null, live = null, fleet = null, lastStats = null, modeFilter = '';
    const REGION = { world: null, asia: [[-10, 60], [45, 150]], europe: [[35, -12], [62, 35]], america: [[-5, -130], [55, -60]], oceania: [[-48, 110], [-8, 180]] };

    function startLive(id, fit) {
        const spec = specs[id]; if (!spec) { return; }
        if (live) { live.stop(); }
        if (activeId !== null && staticMk[activeId] && !markers.hasLayer(staticMk[activeId])) { markers.addLayer(staticMk[activeId]); }
        activeId = id;
        if (staticMk[id]) { markers.removeLayer(staticMk[id]); }
        live = CT.liveRoute(map, routeLayer, Object.assign({ fit: fit }, spec));
    }
    function popup(pos) {
        const box = document.createElement('div');
        const a = document.createElement('a'); a.href = pos.url; a.textContent = pos.shipment_number; a.style.fontWeight = '600'; box.appendChild(a);
        [CT.modeLabel(pos.mode) + ' · ' + CT.tx(pos.status) + (pos.delayed ? ' (' + CT.tx('Delayed').toLowerCase() + ')' : ''), pos.label || CT.tx('Unknown location'), CT.tx('Updated') + ' ' + pos.updated_at].forEach((t) => {
            const d = document.createElement('div'); d.textContent = t; box.appendChild(d);
        });
        return box;
    }
    function renderMap(d) {
        lastStats = d; routeLayer.clearLayers(); overviewLayer.clearLayers(); markers.clearLayers(); specs = {}; staticMk = {};
        const bounds = [], seenDest = {};
        const inCountry = d.positions.filter(CT.inCountry);
        const shown = modeFilter ? inCountry.filter((p) => p.mode === modeFilter) : inCountry;
        shown.forEach((p) => {
            const ll = [Number(p.latitude), Number(p.longitude)];
            const hasDest = p.dest_lat != null && p.dest_lng != null && isFinite(Number(p.dest_lat)) && isFinite(Number(p.dest_lng));
            const dest = hasDest ? [Number(p.dest_lat), Number(p.dest_lng)] : null;
            const brg = dest ? CT.bearing(ll, dest) : 0;
            const isDone = p.status === 'DELIVERED';
            const m = CT.vehicle(p.mode, ll, p.delayed, brg, isDone ? 'done' : 'moving').bindPopup(popup(p));
            staticMk[p.id] = m;
            if (dest) {
                specs[p.id] = { from: (p.origin_lat != null && p.origin_lng != null) ? [Number(p.origin_lat), Number(p.origin_lng)] : ll, here: ll, dest: dest,
                    mode: p.mode, delayed: p.delayed, done: isDone, state: isDone ? 'done' : 'moving', arrived: p.status === 'ARRIVED', lastAt: p.last_ts, eta: p.eta_ts, dep: p.dep_ts, arr: p.arr_ts, popup: popup(p) };
                m.on('click', () => startLive(p.id, true));
            }
            markers.addLayer(m); bounds.push(ll);
            if (dest) {
                CT.quickLine(overviewLayer, ll, dest, p.mode, Object.assign({}, CT.routeStyle(p.mode), { opacity: 0.55, weight: 2 }));
                const key = dest[0].toFixed(3) + ',' + dest[1].toFixed(3);
                if (!seenDest[key]) { seenDest[key] = true; CT.pin(dest).bindPopup(CT.tx('Destination') + ': ' + (p.dest_country || '')).addTo(overviewLayer); }
                bounds.push(dest);
            }
        });
        $('map-empty').classList.toggle('hidden', bounds.length > 0 || modeFilter !== '');
        $('map-filter-empty').classList.toggle('hidden', bounds.length > 0 || modeFilter === '');
        fitRegion(bounds);
        const ids = Object.keys(specs);
        const useId = (activeId !== null && specs[activeId]) ? activeId : (ids.find((k) => !specs[k].done) || ids[0] || null);
        if (fleet) { fleet.stop(); fleet = null; }
        const movers = ids.filter((k) => !specs[k].done && !specs[k].arrived).map((k) => Object.assign({ marker: staticMk[k] }, specs[k]));
        if (movers.length) { fleet = CT.liveFleet(map, movers); }
        if (useId !== null) { startLive(useId, false); } else if (live) { live.stop(); live = null; activeId = null; }
    }
    let lastBounds = [];
    function fitRegion(bounds) {
        if (bounds) { lastBounds = bounds; }
        const r = REGION[$('region-sel').value];
        if (r) { map.fitBounds(r); } else if (lastBounds.length) { map.fitBounds(lastBounds, { padding: [40, 40], maxZoom: 5 }); }
    }
    $('region-sel').addEventListener('change', () => fitRegion());
    document.querySelectorAll('#mode-filter .mode-btn').forEach((b) => {
        b.addEventListener('click', () => { modeFilter = (modeFilter === b.dataset.mode) ? '' : b.dataset.mode; paintModes(); if (lastStats) { renderMap(lastStats); } });
    });
    function paintModes() { document.querySelectorAll('#mode-filter .mode-btn').forEach((b) => b.classList.toggle('on', b.dataset.mode === modeFilter)); }
    paintModes();
    document.addEventListener('ct:country', () => { if (lastStats) { renderMap(lastStats); } });
    document.addEventListener('ct:lang', () => { if (lastStats) { renderMap(lastStats); } });

    /* ---- tombol Perluas / Layar Penuh ---- */
    (function () {
        const wrap = $('map-wrap'), btn = $('map-fs');
        const sync = (on) => {
            wrap.classList.toggle('is-fs', on);
            btn.title = on ? 'Keluar layar penuh' : 'Layar penuh'; btn.setAttribute('aria-label', btn.title); btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            setTimeout(() => map.invalidateSize(), 150);   // Leaflet menghitung ulang ukuran peta
        };
        const fallbackToggle = () => { const on = !wrap.classList.contains('map-fs-fallback'); wrap.classList.toggle('map-fs-fallback', on); sync(on); };
        btn.addEventListener('click', () => {
            if (document.fullscreenElement) { document.exitFullscreen(); return; }
            if (wrap.classList.contains('map-fs-fallback')) { fallbackToggle(); return; }
            if (document.fullscreenEnabled && wrap.requestFullscreen) { wrap.requestFullscreen().catch(fallbackToggle); } else { fallbackToggle(); }
        });
        document.addEventListener('fullscreenchange', () => sync(document.fullscreenElement === wrap));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && wrap.classList.contains('map-fs-fallback')) { fallbackToggle(); } });
    })();


    /* ================= KONDISI GLOBAL: pelabuhan, bandara, cuaca, radar, pesawat live ================= */
    map.setMinZoom(2);
    const condLayer = L.layerGroup().addTo(map);
    const COND = { locs: [], wx: {}, wave: {}, metar: {}, show: { PORT: true, AIRPORT: true, WAREHOUSE: false }, updated: null };
    const TYPE_ICON = { PORT: '⚓', AIRPORT: '✈', WAREHOUSE: '🏭', OTHER: '📍' };
    const TYPE_LABEL = { PORT: 'Pelabuhan', AIRPORT: 'Bandara', WAREHOUSE: 'Gudang', OTHER: 'Lokasi' };
    const WMO = { 0: 'Cerah', 1: 'Cerah berawan', 2: 'Berawan sebagian', 3: 'Berawan', 45: 'Berkabut', 48: 'Kabut beku', 51: 'Gerimis ringan', 53: 'Gerimis', 55: 'Gerimis lebat', 56: 'Gerimis beku', 57: 'Gerimis beku lebat',
        61: 'Hujan ringan', 63: 'Hujan', 65: 'Hujan lebat', 66: 'Hujan beku', 67: 'Hujan beku lebat', 71: 'Salju ringan', 73: 'Salju', 75: 'Salju lebat', 77: 'Butiran salju', 80: 'Hujan sesaat', 81: 'Hujan sesaat lebat', 82: 'Hujan sesaat sangat lebat',
        85: 'Salju sesaat', 86: 'Salju sesaat lebat', 95: 'Badai petir', 96: 'Badai petir + es', 99: 'Badai petir + es lebat' };
    const LV_TXT = ['Normal', 'Waspada', 'Gangguan'];

    // Ambang kepadatan pelabuhan: jumlah kapal BERLABUH (antre) dalam radius 30 km. Ubah sesuai ukuran pelabuhan.
    const CONGEST_WARN = 8, CONGEST_BAD = 20;
    const FLT = { VFR: 0, MVFR: 1, IFR: 2, LIFR: 2 };
    const FLT_TXT = { VFR: 'VFR (cuaca baik)', MVFR: 'MVFR (jarak pandang menurun)', IFR: 'IFR (instrumen)', LIFR: 'LIFR (sangat rendah)' };

    // Penilaian kondisi: cuaca/ombak (Open-Meteo) + METAR resmi (bandara) + kapal antre dari AIS (pelabuhan).
    // Ini indikator operasional, bukan pengganti NOTAM / informasi resmi otoritas pelabuhan.
    function assess(loc, w, wave) {
        const mt = COND.metar[loc.id], ais = loc.ais;
        if (!w && !mt && !ais) { return { lv: 0, why: ['Data cuaca belum tersedia'], unknown: true }; }
        let lv = 0; const why = [];
        const up = (l, t) => { if (l > lv) { lv = l; } why.push(t); };
        const air = loc.type === 'AIRPORT';
        if (w) {
            const g = Number(w.wind_gusts_10m || 0), vis = (w.visibility == null) ? null : Number(w.visibility), code = Number(w.weather_code);
            if (code >= 95) { up(2, 'Badai petir'); }
            if (g >= (air ? 55 : 60)) { up(2, 'Angin kencang ' + Math.round(g) + ' km/j'); } else if (g >= (air ? 35 : 40)) { up(1, 'Angin ' + Math.round(g) + ' km/j'); }
            if (vis !== null && vis < (air ? 800 : 500)) { up(2, 'Jarak pandang ' + Math.round(vis) + ' m'); } else if (vis !== null && vis < (air ? 3000 : 1500)) { up(1, 'Jarak pandang ' + (vis / 1000).toFixed(1) + ' km'); }
            if ([65, 67, 75, 82, 86, 57, 55].includes(code)) { up(1, WMO[code] || 'Hujan lebat'); }
            if ([45, 48].includes(code)) { up(1, 'Berkabut'); }
            if (!air && wave != null) { if (wave >= 4) { up(2, 'Ombak ' + wave.toFixed(1) + ' m'); } else if (wave >= 2.5) { up(1, 'Ombak ' + wave.toFixed(1) + ' m'); } }
        }
        if (air && mt && mt.cat && FLT[mt.cat] !== undefined && FLT[mt.cat] > 0) { up(FLT[mt.cat], 'METAR ' + mt.cat); }
        if (!air && ais) {
            if (ais.anchored >= CONGEST_BAD) { up(2, ais.anchored + ' kapal antre'); } else if (ais.anchored >= CONGEST_WARN) { up(1, ais.anchored + ' kapal antre'); }
        }
        return { lv: lv, why: why };
    }

    function condPopup(loc, a) {
        const w = COND.wx[loc.id], wave = COND.wave[loc.id];
        const rows = [];
        if (w) {
            rows.push(['Cuaca', esc(WMO[w.weather_code] || '-') + ', ' + Math.round(w.temperature_2m) + '°C']);
            rows.push(['Angin', Math.round(w.wind_speed_10m) + ' km/j (hembusan ' + Math.round(w.wind_gusts_10m) + ')']);
            if (w.visibility != null) { rows.push(['Jarak pandang', (w.visibility / 1000).toFixed(1) + ' km']); }
            if (w.precipitation != null) { rows.push(['Curah hujan', w.precipitation + ' mm']); }
        }
        if (loc.type === 'PORT' && wave != null) { rows.push(['Tinggi ombak', wave.toFixed(1) + ' m']); }
        const mt = COND.metar[loc.id];
        if (loc.type === 'AIRPORT' && mt) {
            rows.push(['METAR ' + esc(mt.icao), esc(FLT_TXT[mt.cat] || mt.cat || '-')]);
            const mw = []; if (mt.wind_kmh != null) { mw.push('angin ' + mt.wind_kmh + (mt.gust_kmh ? '–' + mt.gust_kmh : '') + ' km/j'); } if (mt.visib != null) { mw.push('visibilitas ' + esc(mt.visib) + ' mi'); } if (mt.wx) { mw.push(esc(mt.wx)); }
            if (mw.length) { rows.push(['', mw.join(' · ')]); }
            if (mt.raw) { rows.push(['Mentah', '<code style="font-size:10px;word-break:break-all">' + esc(mt.raw) + '</code>']); }
        }
        if (loc.type === 'PORT') {
            if (loc.ais) {
                rows.push(['Kapal antre (berlabuh)', '<b>' + loc.ais.anchored + '</b>']);
                rows.push(['Tertambat / berlayar', loc.ais.moored + ' / ' + loc.ais.underway]);
                rows.push(['Rata-rata menunggu', loc.ais.avg_wait_min == null ? '–' : (loc.ais.avg_wait_min >= 60 ? (loc.ais.avg_wait_min / 60).toFixed(1) + ' jam' : loc.ais.avg_wait_min + ' mnt') + ' <span style="color:#7a8270">(sejak dipantau)</span>']);
            } else { rows.push(['Kapal antre', '<span style="color:#7a8270">belum ada data AIS</span>']); }
        }
        rows.push(['Pengiriman aktif', 'masuk ' + loc.active_in + ' · keluar ' + loc.active_out]);
        rows.push(['Terlambat / eksepsi', loc.delayed + ' / ' + loc.open_exceptions]);
        const color = ['#3FAE49', '#F5A524', '#E5484D'][a.lv];
        return '<div class="cond-pop"><b>' + esc(loc.name) + '</b> <span style="color:#7a8270">(' + esc(loc.code) + ' · ' + esc(loc.country) + ')</span>'
            + '<div style="margin:3px 0"><span style="background:' + color + ';color:#fff;border-radius:9999px;padding:1px 8px;font-weight:700">' + TYPE_LABEL[loc.type] + ': ' + (a.unknown ? 'Tidak ada data' : LV_TXT[a.lv]) + '</span></div>'
            + (a.why.length && !a.unknown ? '<div style="font-size:11px;color:#555">' + esc(a.why.join(' · ')) + '</div>' : '')
            + '<table style="margin-top:4px;font-size:12px">' + rows.map((r) => '<tr><td style="color:#7a8270">' + r[0] + '</td><td>' + r[1] + '</td></tr>').join('') + '</table></div>';
    }

    function paintConditions() {
        condLayer.clearLayers();
        const tally = { PORT: [0, 0, 0], AIRPORT: [0, 0, 0] };
        COND.locs.forEach((loc) => {
            const a = assess(loc, COND.wx[loc.id], COND.wave[loc.id]);
            if (tally[loc.type] && !a.unknown) { tally[loc.type][a.lv]++; }
            if (!COND.show[loc.type]) { return; }
            const icon = L.divIcon({ className: '', html: '<div class="cond-pin lv' + a.lv + '">' + (TYPE_ICON[loc.type] || '📍') + '</div>', iconSize: [28, 28], iconAnchor: [14, 14] });
            L.marker([Number(loc.latitude), Number(loc.longitude)], { icon: icon, zIndexOffset: 200 }).bindPopup(condPopup(loc, a), { maxWidth: 280 }).bindTooltip(loc.name + ' · ' + (a.unknown ? '–' : LV_TXT[a.lv]), { direction: 'top' }).addTo(condLayer);
        });
        const chip = (label, t) => '<span class="cond-chip">' + label + ' <span style="color:#3FAE49">● ' + t[0] + '</span> <span style="color:#F5A524">● ' + t[1] + '</span> <span style="color:#E5484D">● ' + t[2] + '</span></span>';
        $('cond-summary').innerHTML = chip('⚓ Pelabuhan', tally.PORT) + chip('✈ Bandara', tally.AIRPORT)
            + (COND.locs.some((l) => l.ais) ? '<span class="cond-chip">🚢 Kapal antre: <b>' + COND.locs.reduce((n, l) => n + (l.ais ? l.ais.anchored : 0), 0) + '</b></span>' : '<span class="cond-chip" title="Jalankan scripts/ais-listener.js">🚢 AIS belum aktif</span>')
            + '<span class="text-ink-500">Cuaca &amp; laut: Open-Meteo · METAR: NOAA · Kapal: AISStream' + (COND.updated ? ' · diperbarui ' + COND.updated : '') + '</span>';
    }

    const OM = (u) => fetch(u).then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); });
    const asArr = (j) => Array.isArray(j) ? j : [j];
    async function loadConditions() {
        const r = await fetch('{{ route('dashboard.locations') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!r.ok) { throw new Error('HTTP ' + r.status); }
        COND.locs = await r.json();
        if (!COND.locs.length) { paintConditions(); return; }
        const lat = COND.locs.map((l) => Number(l.latitude).toFixed(3)).join(','), lng = COND.locs.map((l) => Number(l.longitude).toFixed(3)).join(',');
        try {
            const wx = asArr(await OM('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lng + '&current=temperature_2m,wind_speed_10m,wind_gusts_10m,precipitation,weather_code,visibility&wind_speed_unit=kmh&timezone=UTC'));
            COND.locs.forEach((l, i) => { COND.wx[l.id] = wx[i] && wx[i].current ? wx[i].current : null; });
        } catch (e) { console.warn('Cuaca gagal dimuat', e); }
        try {   // METAR resmi bandara, lewat server Laravel (cache 10 menit)
            const mr = await fetch('{{ route('dashboard.metar') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            COND.metar = mr.ok ? await mr.json() : {};
        } catch (e) { console.warn('METAR gagal dimuat', e); }
        try {   // tinggi ombak hanya untuk pelabuhan
            const ports = COND.locs.filter((l) => l.type === 'PORT');
            const m = asArr(await OM('https://marine-api.open-meteo.com/v1/marine?latitude=' + ports.map((l) => Number(l.latitude).toFixed(3)).join(',') + '&longitude=' + ports.map((l) => Number(l.longitude).toFixed(3)).join(',') + '&current=wave_height&timezone=UTC'));
            ports.forEach((l, i) => { const v = m[i] && m[i].current ? m[i].current.wave_height : null; COND.wave[l.id] = (v == null) ? null : Number(v); });
        } catch (e) { console.warn('Data laut gagal dimuat', e); }
        COND.updated = new Date().toISOString().slice(11, 16) + ' UTC';
        paintConditions();
    }
    document.querySelectorAll('#layer-toggles input[data-lyr]').forEach((c) => c.addEventListener('change', () => { COND.show[c.dataset.lyr] = c.checked; paintConditions(); }));
    $('cond-refresh').addEventListener('click', () => loadConditions().catch(() => {}));
    setInterval(() => { if (!document.hidden) { loadConditions().catch(() => {}); } }, 600000);

    /* ---- Radar cuaca (RainViewer) & peta laut (OpenSeaMap) ---- */
    let radarLayer = null;
    $('lyr-radar').addEventListener('change', async (e) => {
        if (radarLayer) { map.removeLayer(radarLayer); radarLayer = null; }
        if (!e.target.checked) { return; }
        try {
            const j = await OM('https://api.rainviewer.com/public/weather-maps.json');
            const f = j.radar.past[j.radar.past.length - 1];
            radarLayer = L.tileLayer(j.host + f.path + '/256/{z}/{x}/{y}/2/1_1.png', { opacity: 0.6, maxNativeZoom: 7, attribution: 'Radar © RainViewer' }).addTo(map);
        } catch (err) { e.target.checked = false; alert('Radar cuaca tidak dapat dimuat saat ini.'); }
    });
    const seaLayer = L.tileLayer('https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', { attribution: 'Seamarks © OpenSeaMap' });
    $('lyr-sea').addEventListener('change', (e) => { if (e.target.checked) { seaLayer.addTo(map); } else { map.removeLayer(seaLayer); } });

    /* ---- Pesawat live (OpenSky Network, anonim: dibatasi kuota, jadi hanya saat dicentang & zoom >= 3) ---- */
    const planeLayer = L.layerGroup();
    let planeTimer = null, planeBusy = false;
    const PLANE_SVG = (deg) => '<svg viewBox="0 0 24 24" style="transform:rotate(' + deg + 'deg)"><path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z" fill="#E91E8C" stroke="#fff" stroke-width="1"/></svg>';
    async function loadPlanes() {
        if (planeBusy) { return; }
        if (map.getZoom() < 3) { planeLayer.clearLayers(); return; }
        planeBusy = true;
        try {
            const b = map.getBounds();
            const q = 'lamin=' + Math.max(-85, b.getSouth()).toFixed(2) + '&lomin=' + Math.max(-180, b.getWest()).toFixed(2) + '&lamax=' + Math.min(85, b.getNorth()).toFixed(2) + '&lomax=' + Math.min(180, b.getEast()).toFixed(2);
            const j = await OM('https://opensky-network.org/api/states/all?' + q);
            planeLayer.clearLayers();
            (j.states || []).filter((s) => s[5] != null && s[6] != null && !s[8]).slice(0, 300).forEach((s) => {
                const icon = L.divIcon({ className: 'plane-pin', html: PLANE_SVG(Math.round(s[10] || 0)), iconSize: [18, 18], iconAnchor: [9, 9] });
                L.marker([s[6], s[5]], { icon: icon, interactive: true, zIndexOffset: -100 })
                    .bindTooltip(esc((s[1] || s[0]).trim()) + ' · ' + esc(s[2]) + ' · ' + (s[7] != null ? Math.round(s[7] * 3.28084 / 100) * 100 + ' ft' : '-') + ' · ' + (s[9] != null ? Math.round(s[9] * 3.6) + ' km/j' : '-')).addTo(planeLayer);
            });
        } catch (err) { console.warn('OpenSky gagal', err); }
        planeBusy = false;
    }
    $('lyr-planes').addEventListener('change', (e) => {
        clearInterval(planeTimer);
        if (e.target.checked) { planeLayer.addTo(map); loadPlanes(); planeTimer = setInterval(loadPlanes, 60000); }
        else { map.removeLayer(planeLayer); planeLayer.clearLayers(); }
    });
    let planeDeb = null;
    map.on('moveend', () => { if ($('lyr-planes').checked) { clearTimeout(planeDeb); planeDeb = setTimeout(loadPlanes, 1500); } });

    loadConditions().catch((e) => { console.warn('Kondisi lokasi gagal', e); });

    /* ================= KARTU & GRAFIK (data dari tabel SQL) ================= */
    const charts = {};
    function mk(id, cfg) { if (charts[id]) { charts[id].destroy(); } charts[id] = new Chart($(id), cfg); }
    const grid = { color: 'rgba(240,64,127,.08)' };
    function fill(ctx, color) { const g = ctx.createLinearGradient(0, 0, 0, 240); g.addColorStop(0, color + '66'); g.addColorStop(1, color + '05'); return g; }

    function renderOverview(d) {
        // KPI + perubahan + garis kecil
        ['total', 'transit', 'delivered', 'delayed'].forEach((k) => {
            const v = d.kpi[k]; $('kpi-' + k).textContent = v.value;
            const e = $('kpi-' + k + '-d');
            if (v.delta_pct === null) { e.textContent = ''; } else { e.textContent = (v.delta_pct >= 0 ? '↑ +' : '↓ ') + v.delta_pct + '%'; e.className = 'text-xs font-bold ' + (v.delta_pct >= 0 ? 'delta-up' : 'delta-down'); }
            const s = $('spark-' + k), arr = v.spark || [];
            if (arr.length > 1 && Math.max.apply(null, arr) > 0) {
                const max = Math.max.apply(null, arr), step = 100 / (arr.length - 1);
                const pts = arr.map((x, i) => (i * step).toFixed(1) + ',' + (36 - (x / max) * 32).toFixed(1)).join(' ');
                const col = s.dataset.tone === 'kpi-green' ? GREEN : PINK;
                s.innerHTML = '<polyline fill="none" stroke="' + col + '" stroke-width="1.8" vector-effect="non-scaling-stroke" stroke-linejoin="round" points="' + pts + '"/>';
            } else { s.innerHTML = ''; }
        });
        // Ringkasan Kinerja (garis)
        const ctx = $('chart-perf').getContext('2d');
        mk('chart-perf', { type: 'line', data: { labels: d.trend.labels, datasets: [
            { label: 'Pengiriman', data: d.trend.shipments, borderColor: PINK, backgroundColor: fill(ctx, PINK), fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: PINK },
            { label: 'Terlambat', data: d.trend.delayed, borderColor: GREEN, backgroundColor: fill(ctx, GREEN), fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#fff', pointBorderColor: GREEN } ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, font: { family: 'Manrope' } } } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: grid }, x: { grid: { display: false }, ticks: { maxTicksLimit: 7 } } } } });
        // Donut
        const o = d.donut;
        $('donut-pct').textContent = o.pct_on_time === null ? '–' : o.pct_on_time + '%';
        $('lg-ok').textContent = o.pct_on_time === null ? '–' : o.pct_on_time + '% (' + o.on_time + ')';
        $('lg-late').textContent = o.pct_late === null ? '–' : o.pct_late + '% (' + o.late + ')';
        mk('chart-donut', { type: 'doughnut', data: { labels: ['Tepat Waktu', 'Terlambat'], datasets: [{ data: o.total ? [o.on_time, o.late] : [1, 0], backgroundColor: [PINK, GREEN], borderWidth: 4, borderColor: '#fff' }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false } } } });
        // Grafik per status (batang harian)
        mk('chart-status', { type: 'bar', data: { labels: d.trend.labels, datasets: [
            { label: 'Tepat Waktu', data: d.trend.on_time, backgroundColor: GREEN, borderRadius: 3 },
            { label: 'Terlambat', data: d.trend.delayed, backgroundColor: PINK, borderRadius: 3 } ] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: grid }, x: { grid: { display: false }, ticks: { maxTicksLimit: 6 } } } } });
        // Info Penting
        const TXT = { late24: ['#FF5FA2', (n) => n + ' pengiriman terlambat > 24 jam'], exception: ['#3B82F6', (n) => n + ' exception masih terbuka'], payout: ['#F59E0B', (n) => n + ' pembayaran menunggu settlement'] };
        $('info-list').innerHTML = d.info.map((i) => '<li><a href="' + URLS[i.key] + '" class="flex items-center gap-3 rounded-xl bg-white/70 px-3 py-2.5 hover:bg-white"><span class="h-2.5 w-2.5 flex-none rounded-full" style="background:' + TXT[i.key][0] + '"></span><span class="flex-1 font-semibold text-ink-800">' + esc(TXT[i.key][1](i.count)) + '</span><span class="text-ink-400">›</span></a></li>').join('');
        // Pengiriman Terbaru
        $('recent-body').innerHTML = d.recent.length ? d.recent.map((r) => '<tr><td class="td whitespace-nowrap text-xs"><a class="font-semibold hover:underline" href="' + esc(r.url) + '">' + esc(r.number) + '</a></td><td class="td text-xs">' + esc(r.route) + '</td><td class="td"><span class="pill pill-' + esc(r.tone) + '">' + esc(r.label) + '</span></td><td class="td whitespace-nowrap text-xs">' + esc(r.eta) + '</td></tr>').join('')
            : '<tr><td class="td" colspan="4">Belum ada pengiriman.</td></tr>';
        // Top Rute
        $('top-routes').innerHTML = d.top_routes.length ? d.top_routes.map((r, i) => '<li class="flex items-center gap-3"><span class="grid h-7 w-7 flex-none place-items-center rounded-full bg-pink-100 text-xs font-extrabold text-sakura-600">' + (i + 1) + '</span><div class="min-w-0 flex-1"><p class="truncate font-semibold">' + esc(r.route) + '</p><div class="mt-1 h-1.5 rounded-full bg-pink-100"><div class="h-1.5 rounded-full" style="width:' + Math.min(100, r.pct) + '%;background:linear-gradient(90deg,#FF6FAE,#F0407F)"></div></div></div><b class="text-base">' + r.pct + '%</b></li>').join('')
            : '<li class="text-ink-500">Belum ada data rute.</li>';
        // Aktivitas Sistem
        const AI = { created: 'M12 5v14M5 12h14', tracking: 'M4 4v6h6M20 20v-6h-6M20 10A8 8 0 0 0 6 6L4 10m16 4-2 4a8 8 0 0 1-14-4', exception: 'M12 8v4m0 4h.01M12 3 2 20h20L12 3Z' };
        $('activity').innerHTML = d.activity.length ? d.activity.map((a) => '<li class="flex items-center gap-3"><span class="w-11 flex-none text-xs text-ink-500" title="' + esc(a.at) + '">' + esc(a.time) + '</span><span class="act-ico act-' + esc(a.kind) + '"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="' + AI[a.kind] + '"/></svg></span><span class="flex-1 text-xs font-medium">' + esc(a.text) + '</span></li>').join('')
            : '<li class="text-ink-500">Belum ada aktivitas.</li>';
    }

    /* ================= PEMUAT DATA ================= */
    const sel = $('carrier-sel'); let days = 30, carrier = '', carriersLoaded = false;
    function loadOverview() {
        const qs = '?days=' + days + (carrier ? '&carrier_id=' + encodeURIComponent(carrier) : '');
        return fetch('{{ route('dashboard.overview') }}' + qs, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
            .then((d) => {
                if (!carriersLoaded) { carriersLoaded = true; d.carriers.forEach((c) => { const op = document.createElement('option'); op.value = c.id; op.textContent = c.name; sel.appendChild(op); }); }
                renderOverview(d);
            });
    }
    document.querySelectorAll('.days-sel').forEach((s) => s.addEventListener('change', () => {
        days = Number(s.value); document.querySelectorAll('.days-sel').forEach((o) => { o.value = String(days); }); loadOverview().catch(fail);
    }));
    sel.addEventListener('change', () => { carrier = sel.value; loadOverview().catch(fail); });
    function fail(e) { console.error('Dashboard error:', e); $('dash-error').classList.remove('hidden'); }

    loadOverview().catch(fail);
    fetch('{{ route('dashboard.stats') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
        .then(renderMap).catch(fail);
})();
</script>
@endpush
