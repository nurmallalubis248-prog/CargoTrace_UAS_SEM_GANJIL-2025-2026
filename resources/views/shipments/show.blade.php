@extends('layouts.app')
@section('title', $shipment->shipment_number)

@section('content')
<div class="mb-4 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-extrabold tracking-tight text-matcha-900">{{ $shipment->shipment_number }}</h1>
    @include('partials.badge', ['class' => $shipment->current_status->badgeClass(), 'text' => $shipment->current_status->value])
    @include('partials.delay', ['shipment' => $shipment])
    <div class="ml-auto flex gap-2">
        @can('update', $shipment)
            <a class="btn-light" href="{{ route('shipments.edit', $shipment) }}">Edit</a>
        @endcan
        @can('delete', $shipment)
            @if($shipment->current_status->value === 'CREATED')
                <form method="POST" action="{{ route('shipments.destroy', $shipment) }}" onsubmit="return confirm('Delete this shipment?');">
                    @csrf @method('DELETE')
                    <button class="btn-danger" type="submit">Delete</button>
                </form>
            @endif
        @endcan
        <a class="btn-light" href="{{ route('shipments.index') }}">Back</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <h2 class="mb-3 font-semibold">Details</h2>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm md:grid-cols-3">
            <div><dt class="label">Customer</dt><dd>{{ $shipment->customer->name }}</dd></div>
            <div><dt class="label">Carrier</dt><dd>{{ $shipment->carrier->name }}</dd></div>
            <div><dt class="label">Reference</dt><dd>{{ $shipment->reference_number ?? '-' }}</dd></div>
            <div><dt class="label">Origin</dt><dd>{{ $shipment->origin->name }}</dd></div>
            <div><dt class="label">Destination</dt><dd>{{ $shipment->destination->name }}</dd></div>
            <div><dt class="label">Cargo</dt><dd>{{ config('cargotrace.cargo_types.'.$shipment->cargo_type, $shipment->cargo_type) }}</dd></div>
            <div><dt class="label">Weight</dt><dd>{{ number_format($shipment->weight_kg, 2) }} kg</dd></div>
            <div><dt class="label">Volume</dt><dd>{{ $shipment->volume_cbm !== null ? number_format($shipment->volume_cbm, 2).' m³' : '-' }}</dd></div>
            <div><dt class="label">Created by</dt><dd>{{ $shipment->creator->name }}</dd></div>
            <div><dt class="label">Expected departure</dt><dd>{{ $shipment->expected_departure_at->format('Y-m-d H:i') }} UTC</dd></div>
            <div><dt class="label">Expected arrival</dt><dd>{{ $shipment->expected_arrival_at->format('Y-m-d H:i') }} UTC</dd></div>
            <div><dt class="label">ETA</dt><dd>{{ $shipment->estimated_arrival_at?->format('Y-m-d H:i') ?? '-' }} UTC</dd></div>
            <div><dt class="label">Actual arrival</dt><dd>{{ $shipment->actual_arrival_at?->format('Y-m-d H:i') ?? '-' }}</dd></div>
            <div class="col-span-2"><dt class="label">Current location</dt>
                <dd>{{ $shipment->current_location_label ?? 'Unknown' }}
                    @if($shipment->last_event_at)<span class="text-xs text-ink-500">(as of {{ $shipment->last_event_at->format('Y-m-d H:i') }} UTC)</span>@endif</dd></div>
        </dl>
        <div class="mt-4 rounded-lg bg-ink-50 p-3 text-sm">
            <p class="label">ETA / delay rule result</p>
            <p>{{ $delay['reason'] }}</p>
        </div>
        @if($shipment->items->isNotEmpty())
            <h3 class="mt-4 mb-1 text-sm font-semibold">Items</h3>
            <ul class="list-disc pl-5 text-sm">
                @foreach($shipment->items as $item)
                    <li>{{ $item->quantity }} &times; {{ $item->description }}@if($item->weight_kg) ({{ $item->weight_kg }} kg)@endif</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <h2 class="mb-1 font-semibold">Weather at current position</h2>
        <p class="mb-3 text-xs text-ink-500">External API (Open-Meteo). Informational only &mdash; not a delay prediction.</p>
        <div id="weather" class="text-sm text-ink-600">Loading&hellip;</div>
    </div>
</div>

@include('payments._card', ['shipment' => $shipment->loadMissing('payments')])

<div class="card mt-4">
    <h2 class="mb-2 font-semibold">Route map</h2>
    <div id="map" class="h-96 w-full rounded-lg"></div>
    <p class="mt-2 text-xs text-ink-500">
        <span class="text-matcha-600">&#9679;</span> origin &nbsp;
        <span class="text-red-600">&#9679;</span> destination &nbsp;
        <span class="text-matcha-600">&#9679;</span> tracking points &nbsp;
        <span class="text-red-700">&#9679;</span> exception
    </p>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="card">
        <h2 class="mb-3 font-semibold">Tracking timeline</h2>
        <ol class="relative ml-3 border-l border-ink-200">
            @forelse($events as $e)
                <li class="mb-5 ml-4">
                    <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full {{ $e->is_exception ? 'bg-red-600' : 'bg-matcha-600' }}"></span>
                    <p class="text-sm font-semibold {{ $e->is_exception ? 'text-red-700' : '' }}">
                        {{ $e->is_exception ? 'EXCEPTION' : ($e->status?->value ?? str_replace('_', ' ', $e->event_type)) }}
                    </p>
                    <p class="text-xs text-ink-500">{{ $e->occurred_at->format('Y-m-d H:i') }} UTC
                        @if($e->location_label) &middot; {{ $e->location_label }}@endif
                        @if($e->creator) &middot; {{ $e->creator->name }}@endif</p>
                    @if($e->description)<p class="mt-1 text-sm text-ink-700">{{ $e->description }}</p>@endif
                </li>
            @empty
                <li class="ml-4 text-sm text-ink-500">No events yet.</li>
            @endforelse
        </ol>
    </div>

    <div class="space-y-4">
        <div class="card">
            <h2 class="mb-3 font-semibold">Exceptions</h2>
            @forelse($exceptions as $x)
                <div class="mb-3 rounded-lg border border-ink-200 p-3 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold">{{ $x->type->value }}</span>
                        @include('partials.badge', ['class' => $x->severity->badgeClass(), 'text' => $x->severity->value])
                        @include('partials.badge', ['class' => $x->status->badgeClass(), 'text' => $x->status->value])
                    </div>
                    <p class="mt-1">{{ $x->description }}</p>
                    <p class="mt-1 text-xs text-ink-500">Detected {{ $x->detected_at->format('Y-m-d H:i') }} UTC
                        @if($x->resolved_at) &middot; Resolved {{ $x->resolved_at->format('Y-m-d H:i') }} UTC @endif</p>
                    @if($x->resolution_notes)<p class="mt-1 text-xs">Notes: {{ $x->resolution_notes }}</p>@endif

                    @can('manageExceptions', $shipment)
                        @if($x->status->allowedNext())
                            <form method="POST" action="{{ route('exceptions.update', $x) }}" class="mt-2 flex flex-wrap gap-2">
                                @csrf @method('PATCH')
                                <select class="input w-auto" name="status">
                                    @foreach($x->status->allowedNext() as $n)
                                        <option value="{{ $n->value }}">{{ $n->value }}</option>
                                    @endforeach
                                </select>
                                <input class="input flex-1" type="text" name="resolution_notes" placeholder="Notes (optional)">
                                <button class="btn" type="submit">Update</button>
                            </form>
                        @endif
                    @endcan
                </div>
            @empty
                <p class="text-sm text-ink-500">No exceptions.</p>
            @endforelse

            @can('manageExceptions', $shipment)
                @if($shipment->current_status->value !== 'CANCELLED')
                    <form method="POST" action="{{ route('shipments.exceptions.store', $shipment) }}" class="mt-4 space-y-2 border-t pt-4">
                        @csrf
                        <p class="text-sm font-semibold">Open an exception</p>
                        <div class="grid grid-cols-2 gap-2">
                            <select class="input" name="type" required>
                                @foreach(\App\Enums\ExceptionType::cases() as $t)<option value="{{ $t->value }}">{{ $t->value }}</option>@endforeach
                            </select>
                            <select class="input" name="severity" required>
                                @foreach(\App\Enums\Severity::cases() as $sv)<option value="{{ $sv->value }}">{{ $sv->value }}</option>@endforeach
                            </select>
                        </div>
                        <textarea class="input" name="description" rows="2" required placeholder="What happened?"></textarea>
                        <button class="btn" type="submit">Open exception</button>
                    </form>
                @endif
            @endcan
        </div>

        @can('addTracking', $shipment)
            @if(! $shipment->current_status->isTerminal())
                <div class="card">
                    <h2 class="mb-3 font-semibold">Add tracking event</h2>
                    <form method="POST" action="{{ route('shipments.tracking.store', $shipment) }}" class="space-y-3" id="tracking-form">
                        @csrf
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="label">Event type</label>
                                <select class="input" name="event_type" id="event_type">
                                    <option value="STATUS_UPDATE">Status update</option>
                                    <option value="CHECKPOINT">Position checkpoint</option>
                                    <option value="ETA_UPDATE">ETA update</option>
                                </select>
                            </div>
                            <div id="status-box">
                                <label class="label">New status</label>
                                <select class="input" name="status">
                                    @foreach($shipment->current_status->allowedNext() as $n)<option value="{{ $n->value }}">{{ $n->value }}</option>@endforeach
                                </select>
                            </div>
                            <div id="eta-box" class="hidden">
                                <label class="label">New ETA (UTC)</label>
                                <input class="input" type="datetime-local" name="estimated_arrival_at">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="label">Known location</label>
                                <select class="input" name="location_id">
                                    <option value="">None</option>
                                    @foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">Location label</label>
                                <input class="input" type="text" name="location_label" maxlength="255">
                            </div>
                            <div>
                                <label class="label">Latitude</label>
                                <input class="input" type="number" step="any" name="latitude" min="-90" max="90">
                            </div>
                            <div>
                                <label class="label">Longitude</label>
                                <input class="input" type="number" step="any" name="longitude" min="-180" max="180">
                            </div>
                        </div>
                        <div>
                            <label class="label">Occurred at (UTC, empty = now)</label>
                            <input class="input" type="datetime-local" name="occurred_at">
                        </div>
                        <textarea class="input" name="description" rows="2" placeholder="Description"></textarea>
                        <button class="btn" type="submit">Add event</button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/ct-map.js') }}?v={{ filemtime(public_path('js/ct-map.js')) }}"></script>
<script src="{{ asset('js/ct-live.js') }}?v={{ filemtime(public_path('js/ct-live.js')) }}"></script>
<script>
(function () {
    const data = @json($mapData);
    const map = CT.baseMap('map', [15, 80], 3);

    function popup(lines) {
        const box = document.createElement('div');
        lines.filter(Boolean).forEach((t, i) => {
            const d = document.createElement('div'); d.textContent = CT.tx(t);
            if (i === 0) { d.style.fontWeight = '600'; }
            box.appendChild(d);
        });
        return box;
    }

    const bounds = [];
    const path = [[data.origin.lat, data.origin.lng]];

    L.circleMarker([data.origin.lat, data.origin.lng], { radius: 9, color: '#26331A', fillColor: '#506E33', fillOpacity: 1 })
        .bindPopup(popup(['Origin', data.origin.name])).addTo(map);
    bounds.push([data.origin.lat, data.origin.lng]);

    data.points.forEach((p) => {
        L.circleMarker([p.lat, p.lng], {
            radius: 6, color: p.exception ? '#9E3256' : '#3F5729',
            fillColor: p.exception ? '#E67B9C' : '#86A85D', fillOpacity: 0.9
        }).bindPopup(popup([p.exception ? 'EXCEPTION' : (p.status || p.type), p.time, p.label])).addTo(map);
        if (!p.exception) { path.push([p.lat, p.lng]); }
        bounds.push([p.lat, p.lng]);
    });

    CT.pin([data.destination.lat, data.destination.lng]).bindPopup(popup(['Destination', data.destination.name])).addTo(map);
    bounds.push([data.destination.lat, data.destination.lng]);

    const here = path[path.length - 1], dest = [data.destination.lat, data.destination.lng];
    const routes = L.layerGroup().addTo(map);
    // Real-road route (ROAD), great-circle (SEA/AIR), travelled trail, and a vehicle that keeps moving toward the ETA.
    CT.liveRoute(map, routes, { from: path[0], here: here, dest: dest, mode: data.mode, delayed: data.delayed, done: data.done,
        lastAt: data.last_ts, eta: data.eta_ts, popup: popup([data.number, CT.modeLabel(data.mode)]), fit: false });
    map.fitBounds(bounds, { padding: [40, 40] });

    // Weather (external API; failures are shown, never thrown)
    const w = document.getElementById('weather');
    fetch('{{ route('shipments.weather', $shipment) }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
        .then((d) => {
            w.textContent = '';
            const add = (t, bold) => { const p = document.createElement('p'); p.textContent = t; if (bold) { p.className = 'font-semibold text-ink-800'; } w.appendChild(p); };
            add(d.location.label, true);
            if (!d.available) { add('Weather unavailable: ' + d.message); return; }
            add(d.condition + ', ' + d.temperature_c + ' °C');
            add('Wind ' + d.wind_kmh + ' km/h · Precipitation ' + d.precipitation_mm + ' mm');
            add('Observed ' + d.observed_at + ' UTC · ' + d.source);
        })
        .catch(() => { w.textContent = 'Weather could not be loaded right now.'; });

    // Toggle tracking form fields by event type
    const type = document.getElementById('event_type');
    if (type) {
        const sync = () => {
            document.getElementById('status-box').classList.toggle('hidden', type.value !== 'STATUS_UPDATE');
            document.getElementById('eta-box').classList.toggle('hidden', type.value !== 'ETA_UPDATE');
        };
        type.addEventListener('change', sync); sync();
    }
})();
</script>
@endpush
