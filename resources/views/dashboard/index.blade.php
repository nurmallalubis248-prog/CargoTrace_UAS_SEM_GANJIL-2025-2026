@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-2xl font-extrabold tracking-tight text-matcha-900">Dashboard</h1>
    <p class="text-sm text-ink-500">{{ auth()->user()->isCustomer() ? 'Your shipments' : 'All shipments' }} &middot; live data from the database</p>
</div>

<div id="dash-error" class="mb-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
    Could not load dashboard data. Please refresh the page.
</div>

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    @foreach([
        ['k-shipments', 'Total shipments'], ['k-transit', 'In transit'], ['k-delivered', 'Delivered'],
        ['k-delayed', 'Delayed'], ['k-exceptions', 'Open exceptions'], ['k-delay-rate', 'Delay rate'],
    ] as [$id, $label])
        <div class="card">
            <p class="text-xs font-semibold text-ink-500">{{ $label }}</p>
            <p id="{{ $id }}" class="mt-1 text-3xl font-bold">&ndash;</p>
        </div>
    @endforeach
</div>

<div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="card"><p class="text-xs font-semibold text-ink-500">Exception rate</p><p id="k-exc-rate" class="mt-1 text-2xl font-bold">&ndash;</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">On-time delivery rate</p><p id="k-ontime" class="mt-1 text-2xl font-bold">&ndash;</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Avg delay (delayed shipments) &middot; Avg exception resolution</p><p id="k-avg" class="mt-1 text-2xl font-bold">&ndash;</p></div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="card">
        <h2 class="mb-3 font-semibold">Shipments by status</h2>
        <div class="relative h-64"><canvas id="chart-status"></canvas></div>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold">Shipments by carrier</h2>
        <div class="relative h-64"><canvas id="chart-carrier"></canvas></div>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold">Delays by severity</h2>
        <div class="relative h-64"><canvas id="chart-severity"></canvas></div>
    </div>
</div>

<div class="card mt-4">
    <h2 class="mb-1 font-semibold">Active shipments &mdash; latest known position</h2>
    <p id="map-empty" class="mb-2 hidden text-sm text-ink-500">No active shipments with a known position.</p>
    <div id="map" class="h-96 w-full rounded-lg"></div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const set = (id, text) => { $(id).textContent = text; };
    const pct = (v) => (v === null || v === undefined) ? 'n/a' : v + '%';
    const hrs = (v) => (v === null || v === undefined) ? 'n/a' : v + ' h';

    const map = L.map('map').setView([15, 80], 2);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    const markers = L.layerGroup().addTo(map);

    const charts = {};
    function draw(id, type, labels, values, colors) {
        if (charts[id]) { charts[id].destroy(); }
        charts[id] = new Chart($(id), {
            type: type,
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, borderRadius: type === 'bar' ? 6 : 0 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: type === 'doughnut', position: 'bottom', labels: { usePointStyle: true, font: { family: 'Manrope' } } } },
                scales: type === 'bar' ? { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EEF0EA' } }, x: { grid: { display: false } } } : {}
            }
        });
    }

    function popup(pos) {
        const box = document.createElement('div');
        const a = document.createElement('a');
        a.href = pos.url; a.textContent = pos.shipment_number; a.style.fontWeight = '600';
        box.appendChild(a);
        [pos.status + (pos.delayed ? ' (delayed)' : ''), pos.label || 'Unknown location', 'Updated ' + pos.updated_at].forEach((t) => {
            const d = document.createElement('div'); d.textContent = t; box.appendChild(d);
        });
        return box;
    }

    function render(d) {
        set('k-shipments', d.totals.shipments);
        set('k-transit', d.totals.in_transit);
        set('k-delivered', d.totals.delivered);
        set('k-delayed', d.totals.delayed);
        set('k-exceptions', d.totals.open_exceptions);
        set('k-delay-rate', pct(d.rates.delay_rate_pct));
        set('k-exc-rate', pct(d.rates.exception_rate_pct));
        set('k-ontime', pct(d.rates.on_time_delivery_rate_pct));
        set('k-avg', hrs(d.rates.avg_delay_hours) + ' · ' + hrs(d.rates.avg_exception_resolution_hours));

        const palette = { CREATED: '#C5CBBB', BOOKED: '#F0A5BC', PICKED_UP: '#E3B341', IN_TRANSIT: '#86A85D', DELIVERED: '#3F5729', CANCELLED: '#B85C6B' };
        const st = Object.keys(d.by_status);
        draw('chart-status', 'doughnut', st, st.map((k) => d.by_status[k]), st.map((k) => palette[k] || '#737B69'));
        const ca = Object.keys(d.by_carrier);
        draw('chart-carrier', 'bar', ca, ca.map((k) => d.by_carrier[k]), '#678B41');
        const sv = Object.keys(d.delay_by_severity);
        const svColor = { LOW: '#E3B341', MEDIUM: '#E67B9C', HIGH: '#B91C1C' };
        draw('chart-severity', 'bar', sv, sv.map((k) => d.delay_by_severity[k]), sv.map((k) => svColor[k] || '#737B69'));

        markers.clearLayers();
        const bounds = [];
        d.positions.forEach((p) => {
            const m = L.circleMarker([p.latitude, p.longitude], {
                radius: 8, color: p.delayed ? '#9E3256' : '#3F5729', fillColor: p.delayed ? '#E67B9C' : '#86A85D', fillOpacity: 0.85
            }).bindPopup(popup(p));
            markers.addLayer(m);
            bounds.push([p.latitude, p.longitude]);
        });
        $('map-empty').classList.toggle('hidden', bounds.length > 0);
        if (bounds.length) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 5 }); }
    }

    fetch('{{ route('dashboard.stats') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
        .then(render)
        .catch(() => $('dash-error').classList.remove('hidden'));
})();
</script>
@endpush
