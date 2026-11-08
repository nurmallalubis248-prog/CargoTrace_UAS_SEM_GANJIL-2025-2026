@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="grad-brand mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl p-6 text-white shadow-lg">
    <div>
        <p class="text-sm text-white/80"><span data-i18n="welcome">Welcome back</span>, {{ auth()->user()->name }}</p>
        <h1 class="text-3xl font-extrabold tracking-tight" data-i18n="dashboard">Dashboard</h1>
        <p class="text-sm text-white/80" data-i18n="{{ auth()->user()->isCustomer() ? 'yourShip' : 'allShip' }}"></p>
    </div>
    <div class="text-right">
        <p class="text-xs font-semibold uppercase tracking-widest text-white/80" data-i18n="clock">Local time</p>
        <p id="big-clock" class="text-4xl font-extrabold tabular-nums">--:--:--</p>
        <p id="big-date" class="text-sm text-white/90"></p>
    </div>
</div>

<div id="dash-error" class="mb-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
    Could not load dashboard data. Please refresh the page.
</div>

@php
    $isAdmin = auth()->user()->isAdmin();
    $row1 = [
        ['k-shipments', 'total', route('shipments.index')],
        ['k-transit', 'transit', route('shipments.index', ['status' => 'IN_TRANSIT'])],
        ['k-delivered', 'delivered', route('shipments.index', ['status' => 'DELIVERED'])],
        ['k-delayed', 'delayed', route('shipments.index', ['delayed' => 1])],
        ['k-exceptions', 'exceptions', route('shipments.index', ['exception' => 'open'])],
        ['k-delay-rate', 'delayRate', route('reports.index', ['delayed' => 1])],
    ];
    $cardCls = 'card block transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-matcha-400';
@endphp
<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    @foreach($row1 as [$id, $label, $href])
        <a href="{{ $href }}" class="{{ $cardCls }}">
            <p class="text-xs font-semibold text-ink-500" data-i18n="{{ $label }}"></p>
            <p id="{{ $id }}" class="mt-1 text-3xl font-bold">&ndash;</p>
        </a>
    @endforeach
</div>

<div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
    <a href="{{ $isAdmin ? route('admin.accounting.index') : route('reports.index') }}" class="{{ $cardCls }}"><p class="text-xs font-semibold text-ink-500" data-i18n="revenue">Revenue received</p><p id="k-paid" class="mt-1 text-2xl font-bold" data-idr="0">&ndash;</p><p id="k-paid-pending" class="text-xs text-ink-500"></p></a>
    <a href="{{ route('shipments.index', ['exception' => 'any']) }}" class="{{ $cardCls }}"><p class="text-xs font-semibold text-ink-500" data-i18n="excRate">Exception rate</p><p id="k-exc-rate" class="mt-1 text-2xl font-bold">&ndash;</p></a>
    <a href="{{ route('shipments.index', ['status' => 'DELIVERED', 'delayed' => 0]) }}" class="{{ $cardCls }}"><p class="text-xs font-semibold text-ink-500" data-i18n="onTime">On-time delivery rate</p><p id="k-ontime" class="mt-1 text-2xl font-bold">&ndash;</p></a>
    <a href="{{ route('shipments.index', ['delayed' => 1]) }}" class="{{ $cardCls }}"><p class="text-xs font-semibold text-ink-500" data-i18n="avg">Avg</p><p id="k-avg" class="mt-1 text-2xl font-bold">&ndash;</p></a>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="card">
        <h2 class="mb-3 font-semibold" data-i18n="byStatus">Shipments by status</h2>
        <div class="relative h-64"><canvas id="chart-status"></canvas></div>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold" data-i18n="byCarrier">Shipments by carrier</h2>
        <div class="relative h-64"><canvas id="chart-carrier"></canvas></div>
    </div>
    <div class="card">
        <h2 class="mb-3 font-semibold" data-i18n="bySeverity">Delays by severity</h2>
        <div class="relative h-64"><canvas id="chart-severity"></canvas></div>
    </div>
</div>

<div class="card mt-4">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="font-semibold">Delayed shipments and reasons</h2>
            <p class="text-xs text-ink-500">The reason comes from the exception reported on the shipment (customs, documents, weather, ...).</p>
        </div>
        <a href="{{ route('shipments.index', ['delayed' => 1]) }}" class="text-xs font-semibold text-matcha-700 hover:underline">View all delayed</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-ink-200 text-sm">
            <thead class="bg-ink-50"><tr><th class="th">Shipment</th><th class="th">Route</th><th class="th">Delay</th><th class="th">Reason</th></tr></thead>
            <tbody id="delayed-body" class="divide-y divide-ink-100"></tbody>
        </table>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4">
    <div class="card">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="font-semibold" data-i18n="liveMap">Live shipment map</h2><p class="text-xs text-ink-500" data-i18n="liveMapSub"></p></div>
            <div class="flex gap-3 text-xs font-semibold text-ink-600">
                <span>✈ <span data-i18n="modeAir">Air</span></span><span>🚚 <span data-i18n="modeRoad">Road</span></span><span>🚢 <span data-i18n="modeSea">Sea</span></span>
            </div>
        </div>
        <p id="map-empty" class="mb-2 hidden text-sm text-ink-500" data-i18n="noPos"></p>
        <div id="map" class="h-[32rem] w-full rounded-xl" data-no-i18n></div>
    </div>
</div>
<div class="card mt-4">
    <h2 class="font-semibold">Delay forecast</h2>
    <p class="mb-3 text-xs text-ink-500">Statistical model based on lane history and open exceptions (not a guarantee).</p>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-ink-200 text-sm">
            <thead class="bg-ink-50"><tr><th class="th">Shipment</th><th class="th">Route</th><th class="th">Predicted delay</th><th class="th">Predicted ETA</th><th class="th">Risk</th><th class="th">Confidence</th></tr></thead>
            <tbody id="forecast-body" class="divide-y divide-ink-100"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/ct-map.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const set = (id, text) => { $(id).textContent = text; };
    const pct = (v) => (v === null || v === undefined) ? 'n/a' : v + '%';
    const hrs = (v) => (v === null || v === undefined) ? 'n/a' : v + ' h';

    const SHIP_URL = '{{ route('shipments.index') }}';
    const map = CT.baseMap('map', [15, 80], 3);
    const markers = L.layerGroup().addTo(map);
    const overviewLayer = L.layerGroup().addTo(map);   // pin tujuan + garis tipis semua pengiriman
    const routeLayer = L.layerGroup().addTo(map);      // sorotan merah untuk pengiriman yang diklik

    const charts = {};
    function draw(id, type, labels, values, colors, hrefFor) {
        if (charts[id]) { charts[id].destroy(); }
        charts[id] = new Chart($(id), {
            type: type,
            data: { labels: labels.map(CT.tx), datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, borderRadius: type === 'bar' ? 6 : 0 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                onClick: (evt, els) => { if (hrefFor && els.length) { window.location.href = hrefFor(labels[els[0].index]); } },
                onHover: (evt, els) => { evt.native.target.style.cursor = (hrefFor && els.length) ? 'pointer' : 'default'; },
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
        [CT.modeLabel(pos.mode) + ' · ' + CT.tx(pos.status) + (pos.delayed ? ' (' + CT.tx('Delayed').toLowerCase() + ')' : ''), pos.label || CT.tx('Unknown location'), CT.tx('Updated') + ' ' + pos.updated_at].forEach((t) => {
            const d = document.createElement('div'); d.textContent = t; box.appendChild(d);
        });
        return box;
    }

    let lastData = null;
    const RISK = { ON_TIME: 'bg-matcha-100 text-matcha-800', LOW: 'bg-yellow-100 text-yellow-800', MEDIUM: 'bg-sakura-100 text-sakura-700', HIGH: 'bg-red-100 text-red-800' };
    function renderForecast(rows) {
        const tb = $('forecast-body');
        tb.innerHTML = rows.length ? rows.map((r) => '<tr><td class="td"><a class="font-semibold text-matcha-700 hover:underline" href="' + r.url + '">' + r.number + '</a></td><td class="td">' + r.route + '</td><td class="td">+' + r.predicted_delay_hours + ' h</td><td class="td">' + r.predicted_eta + ' UTC</td><td class="td"><span class="rounded-full px-2 py-0.5 text-xs font-semibold ' + RISK[r.risk] + '">' + CT.tx(r.risk) + '</span></td><td class="td">' + r.confidence + '%</td></tr>').join('')
            : '<tr><td class="td" colspan="6">' + CT.tx('No active shipments to forecast.') + '</td></tr>';
    }
    function renderDelayed(rows) {
        const tb = $('delayed-body'); tb.textContent = '';
        if (!rows.length) {
            const tr = document.createElement('tr'), td = document.createElement('td');
            td.className = 'td'; td.colSpan = 4; td.textContent = CT.tx('No delayed shipments.'); tr.appendChild(td); tb.appendChild(tr); return;
        }
        const SEV = { HIGH: 'text-red-700', MEDIUM: 'text-sakura-700', LOW: 'text-yellow-700' };
        rows.forEach((r) => {
            const tr = document.createElement('tr');
            const cell = (node) => { const td = document.createElement('td'); td.className = 'td'; td.appendChild(node); tr.appendChild(td); return td; };
            const a = document.createElement('a'); a.href = r.url; a.textContent = r.number; a.className = 'font-semibold text-matcha-700 hover:underline'; cell(a);
            const route = document.createElement('span'); route.textContent = r.route; route.setAttribute('data-no-i18n', ''); cell(route);
            const dl = document.createElement('span'); dl.textContent = '+' + r.delay_hours + ' h · ' + CT.tx(r.severity || ''); dl.className = 'font-semibold ' + (SEV[r.severity] || ''); cell(dl);
            const why = document.createElement('span');
            why.textContent = r.reason ? CT.tx(r.reason) : CT.tx('Reason not recorded yet');
            if (!r.reason) { why.className = 'text-ink-500'; }
            if (r.detail) { why.title = r.detail; }
            cell(why);
            tb.appendChild(tr);
        });
    }
    document.addEventListener('ct:country', () => { if (lastData) { render(lastData); } });
    function render(d) {
        lastData = d; routeLayer.clearLayers(); overviewLayer.clearLayers();
        $('k-paid').dataset.idr = d.payments.paid_amount; CT.refreshMoney();
        $('k-paid-pending').textContent = d.payments.pending ? d.payments.pending + ' ' + CT.tx('Pending') : '';
        renderForecast(d.forecast || []);
        renderDelayed(d.delayed_list || []);
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
        draw('chart-status', 'doughnut', st, st.map((k) => d.by_status[k]), st.map((k) => palette[k] || '#737B69'), (k) => SHIP_URL + '?status=' + encodeURIComponent(k));
        const ca = Object.keys(d.by_carrier);
        draw('chart-carrier', 'bar', ca, ca.map((k) => d.by_carrier[k]), '#678B41', (k) => SHIP_URL + '?carrier_id=' + encodeURIComponent((d.carrier_ids || {})[k] || ''));
        const sv = Object.keys(d.delay_by_severity);
        const svColor = { LOW: '#E3B341', MEDIUM: '#E67B9C', HIGH: '#B91C1C' };
        draw('chart-severity', 'bar', sv, sv.map((k) => d.delay_by_severity[k]), sv.map((k) => svColor[k] || '#737B69'), (k) => SHIP_URL + '?delayed=1&severity=' + encodeURIComponent(k));

        markers.clearLayers();
        const bounds = [], seenDest = {};
        d.positions.filter(CT.inCountry).forEach((p) => {
            const ll = [Number(p.latitude), Number(p.longitude)];
            const hasDest = p.dest_lat !== null && p.dest_lat !== undefined && p.dest_lng !== null && p.dest_lng !== undefined
                && isFinite(Number(p.dest_lat)) && isFinite(Number(p.dest_lng));
            const dest = hasDest ? [Number(p.dest_lat), Number(p.dest_lng)] : null;
            const brg = dest ? CT.bearing(ll, dest) : 0;
            const m = CT.vehicle(p.mode, ll, p.delayed, brg).bindPopup(popup(p));
            m.on('click', () => {   // show destination pin + remaining route for the clicked shipment
                routeLayer.clearLayers();
                if (!dest) { return; }
                CT.pin(dest).addTo(routeLayer);
                CT.route(routeLayer, [ll, dest], p.mode, { color: '#E0394F', weight: 4, opacity: 0.9 });
            });
            markers.addLayer(m);
            bounds.push(ll);
            if (dest) {   // titik tujuan + garis menuju tujuan langsung tampil
                CT.quickLine(overviewLayer, ll, dest, p.mode, Object.assign({}, CT.routeStyle(p.mode), { opacity: 0.55, weight: 2 }));
                const key = dest[0].toFixed(3) + ',' + dest[1].toFixed(3);
                if (!seenDest[key]) { seenDest[key] = true; CT.pin(dest).bindPopup(CT.tx('Destination') + ': ' + (p.dest_country || '')).addTo(overviewLayer); }
                bounds.push(dest);
            }
        });
        $('map-empty').classList.toggle('hidden', bounds.length > 0);
        if (bounds.length) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 5 }); }
    }

    // Clock + calendar
    const tick = () => { const n = new Date(); $('big-clock').textContent = CT.timeStr(n);
        $('big-date').textContent = CT.dateStr(n, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + ' · ' + CT.tzLabel(); };
    tick(); setInterval(tick, 1000);
    document.addEventListener('ct:lang', () => { tick(); if (lastData) { render(lastData); } });
    document.addEventListener('ct:country', tick);

    fetch('{{ route('dashboard.stats') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
        .then(render)
        .catch((e) => { console.error('Dashboard error:', e); $('dash-error').classList.remove('hidden'); });
})();
</script>
@endpush
