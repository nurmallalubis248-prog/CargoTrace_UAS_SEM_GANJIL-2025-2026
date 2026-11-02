@extends('layouts.app')
@section('title', 'Shipments')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-2xl font-extrabold tracking-tight text-matcha-900">Shipments</h1>
    @if(auth()->user()->isStaff())
        <a href="{{ route('shipments.create') }}" class="btn-accent">New shipment</a>
    @endif
</div>

<form method="GET" action="{{ route('shipments.index') }}" class="card mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
    <div class="md:col-span-2">
        <label class="label">Shipment / reference number</label>
        <input class="input" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="e.g. CT2611 or PO-1234">
    </div>
    <div>
        <label class="label">Status</label>
        <select class="input" name="status">
            <option value="">All</option>
            @foreach(\App\Enums\ShipmentStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->value }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Delay</label>
        <select class="input" name="delayed">
            <option value="">All</option>
            <option value="1" @selected(($filters['delayed'] ?? '') === '1')>Delayed</option>
            <option value="0" @selected(($filters['delayed'] ?? '') === '0')>On time</option>
        </select>
    </div>
    <div>
        <label class="label">Carrier</label>
        <select class="input" name="carrier_id">
            <option value="">All</option>
            @foreach($carriers as $c)
                <option value="{{ $c->id }}" @selected((string) ($filters['carrier_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Origin</label>
        <select class="input" name="origin_id">
            <option value="">All</option>
            @foreach($locations as $l)
                <option value="{{ $l->id }}" @selected((string) ($filters['origin_id'] ?? '') === (string) $l->id)>{{ $l->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Destination</label>
        <select class="input" name="destination_id">
            <option value="">All</option>
            @foreach($locations as $l)
                <option value="{{ $l->id }}" @selected((string) ($filters['destination_id'] ?? '') === (string) $l->id)>{{ $l->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Sort by</label>
        <div class="flex gap-2">
            <select class="input" name="sort">
                @foreach(['created_at' => 'Created', 'expected_arrival_at' => 'Expected arrival', 'estimated_arrival_at' => 'ETA', 'delay_hours' => 'Delay', 'current_status' => 'Status', 'shipment_number' => 'Number'] as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['sort'] ?? 'created_at') === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select class="input" name="dir">
                <option value="desc" @selected(($filters['dir'] ?? 'desc') === 'desc')>Desc</option>
                <option value="asc" @selected(($filters['dir'] ?? '') === 'asc')>Asc</option>
            </select>
        </div>
    </div>
    <div>
        <label class="label">Expected arrival from</label>
        <input class="input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
    </div>
    <div>
        <label class="label">Expected arrival to</label>
        <input class="input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    </div>
    <div class="flex items-end gap-2 md:col-span-2">
        <button class="btn" type="submit">Apply filters</button>
        <a class="btn-light" href="{{ route('shipments.index') }}">Reset</a>
    </div>
</form>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50">
        <tr>
            <th class="th">Number</th><th class="th">Route</th><th class="th">Carrier</th>
            <th class="th">Customer</th><th class="th">Status</th><th class="th">Expected arrival</th>
            <th class="th">ETA</th><th class="th">Delay</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-ink-100">
        @forelse($shipments as $s)
            <tr class="hover:bg-ink-50">
                <td class="td font-semibold"><a class="text-matcha-700 hover:underline" href="{{ route('shipments.show', $s) }}">{{ $s->shipment_number }}</a>
                    @if($s->reference_number)<div class="text-xs font-normal text-ink-500">{{ $s->reference_number }}</div>@endif</td>
                <td class="td">{{ $s->origin->name }} &rarr; {{ $s->destination->name }}</td>
                <td class="td">{{ $s->carrier->name }}</td>
                <td class="td">{{ $s->customer->name }}</td>
                <td class="td">@include('partials.badge', ['class' => $s->current_status->badgeClass(), 'text' => $s->current_status->value])</td>
                <td class="td whitespace-nowrap">{{ $s->expected_arrival_at->format('Y-m-d H:i') }}</td>
                <td class="td whitespace-nowrap">{{ $s->estimated_arrival_at?->format('Y-m-d H:i') ?? '-' }}</td>
                <td class="td">@include('partials.delay', ['shipment' => $s])</td>
            </tr>
        @empty
            <tr><td class="td py-8 text-center text-ink-500" colspan="8">No shipments match your filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $shipments->links() }}</div>
@endsection
