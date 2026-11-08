@extends('layouts.app')
@section('title', 'Reports')

@section('content')
@php
    $s = $report['summary'];
    $pdfUrl = route('reports.pdf', array_filter($filters, fn ($v) => $v !== null && $v !== ''));
@endphp
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-matcha-900">Reports</h1>
        <p class="text-sm text-ink-500">Choose filters, check the preview, then download it as a PDF.</p>
    </div>
    <a href="{{ $pdfUrl }}" class="btn-accent">Download PDF</a>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="card mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
    <div>
        <label class="label">Status</label>
        <select class="input" name="status">
            <option value="">All</option>
            @foreach(\App\Enums\ShipmentStatus::cases() as $st)
                <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ $st->value }}</option>
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
        <label class="label">Exception</label>
        <select class="input" name="exception">
            <option value="">All</option>
            <option value="open" @selected(($filters['exception'] ?? '') === 'open')>Open exceptions</option>
            <option value="any" @selected(($filters['exception'] ?? '') === 'any')>Any exception</option>
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
        <label class="label">Expected arrival from</label>
        <input class="input" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
    </div>
    <div>
        <label class="label">Expected arrival to</label>
        <input class="input" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    </div>
    <div class="flex items-end gap-2 md:col-span-2">
        <button class="btn-accent" type="submit">Apply filters</button>
        <a href="{{ route('reports.index') }}" class="btn-light">Reset</a>
    </div>
</form>

<div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-7">
    <div class="card"><p class="text-xs font-semibold text-ink-500">Total shipments</p><p class="mt-1 text-2xl font-bold">{{ $s['total'] }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">In transit</p><p class="mt-1 text-2xl font-bold">{{ $s['in_transit'] }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Delivered</p><p class="mt-1 text-2xl font-bold">{{ $s['delivered'] }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Delayed</p><p class="mt-1 text-2xl font-bold">{{ $s['delayed'] }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Open exceptions</p><p class="mt-1 text-2xl font-bold">{{ $s['open_exceptions'] }}</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Delay rate</p><p class="mt-1 text-2xl font-bold">{{ $s['delay_rate_pct'] }}%</p></div>
    <div class="card"><p class="text-xs font-semibold text-ink-500">Revenue received</p><p class="mt-1 text-xl font-bold" data-idr="{{ $s['revenue'] }}">Rp {{ number_format($s['revenue'], 0, ',', '.') }}</p></div>
</div>

<div class="card mt-4">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-ink-200 text-sm">
            <thead class="bg-ink-50">
                <tr>
                    <th class="th">Shipment</th><th class="th">Route</th><th class="th">Carrier</th><th class="th">Status</th>
                    <th class="th">Expected arrival</th><th class="th">Delay</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ink-100">
                @forelse($report['rows']->take(50) as $sh)
                    <tr>
                        <td class="td"><a class="font-semibold text-matcha-700 hover:underline" href="{{ route('shipments.show', $sh) }}">{{ $sh->shipment_number }}</a></td>
                        <td class="td" data-no-i18n>{{ $sh->origin?->name }} → {{ $sh->destination?->name }}</td>
                        <td class="td" data-no-i18n>{{ $sh->carrier?->name }}</td>
                        <td class="td">{{ $sh->current_status->value }}</td>
                        <td class="td">{{ $sh->expected_arrival_at?->format('Y-m-d') }}</td>
                        <td class="td">@include('partials.delay', ['shipment' => $sh])</td>
                    </tr>
                @empty
                    <tr><td class="td" colspan="6">No shipments match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($report['total'] > 50)
        <p class="mt-3 text-xs text-ink-500">Showing the first 50 of {{ $report['total'] }} shipments. The PDF contains up to {{ \App\Services\ReportService::MAX_ROWS }}.</p>
    @endif
</div>
@endsection
