@extends('layouts.app')
@section('title', 'Shipments')

@push('head')
@include('partials.sh-styles')
@endpush

@section('content')
@php
    $user = auth()->user();
    $total = $shipments->total();
    $from = $total ? $shipments->firstItem() : 0;
    $to = $total ? $shipments->lastItem() : 0;
    $perPage = (int) ($filters['per_page'] ?? 10);
    $sortOpts = ['created_at' => 'Created', 'expected_arrival_at' => 'Expected arrival', 'estimated_arrival_at' => 'ETA', 'delay_hours' => 'Delay', 'current_status' => 'Status', 'shipment_number' => 'Number'];
    $dir = $filters['dir'] ?? 'desc';

    $ico = [
        'box' => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    ];
@endphp

{{-- Kepala halaman --}}
<div class="sh-head">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ico['box'] }}"/><path d="m7.5 5 9 5.2" stroke-width="1.2"/></svg></span>
        <div>
            <h1 data-i18n="shipments">Shipments</h1>
            <p>Manage shipment data and monitor status in real time</p>
        </div>
    </div>
    @if($user->isStaff())
        <a href="{{ route('shipments.create') }}" class="sh-new">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
            <span data-i18n="newShipment">New shipment</span>
        </a>
    @endif
</div>

{{-- Kartu filter --}}
<form id="shipFilter" method="GET" action="{{ route('shipments.index') }}" class="sh-panel sh-filters">
    <div class="sh-row sh-row-4">
        <div class="sh-search">
            <label class="sh-lbl" for="f-q">Shipment / reference number</label>
            <div style="position:relative">
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input id="f-q" class="sh-fld" type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search shipment number...">
            </div>
        </div>
        <div>
            <label class="sh-lbl" for="f-status">Status</label>
            <select id="f-status" class="sh-sel" name="status">
                <option value="">All</option>
                @foreach(\App\Enums\ShipmentStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ ucwords(strtolower(str_replace('_', ' ', $s->value))) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-delayed">Delay</label>
            <select id="f-delayed" class="sh-sel" name="delayed">
                <option value="">All</option>
                <option value="1" @selected(($filters['delayed'] ?? '') === '1')>Delayed</option>
                <option value="0" @selected(($filters['delayed'] ?? '') === '0')>On time</option>
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-exc">Exception</label>
            <select id="f-exc" class="sh-sel" name="exception">
                <option value="">All</option>
                <option value="open" @selected(($filters['exception'] ?? '') === 'open')>Open exceptions</option>
                <option value="any" @selected(($filters['exception'] ?? '') === 'any')>Any exception</option>
            </select>
        </div>
    </div>

    <div class="sh-row sh-row-5">
        <div>
            <label class="sh-lbl" for="f-carrier">Carrier</label>
            <select id="f-carrier" class="sh-sel" name="carrier_id">
                <option value="">All</option>
                @foreach($carriers as $c)
                    <option value="{{ $c->id }}" @selected((string) ($filters['carrier_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-origin">Origin</label>
            <select id="f-origin" class="sh-sel" name="origin_id">
                <option value="">All</option>
                @foreach($locations as $l)
                    <option value="{{ $l->id }}" @selected((string) ($filters['origin_id'] ?? '') === (string) $l->id)>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-dest">Destination</label>
            <select id="f-dest" class="sh-sel" name="destination_id">
                <option value="">All</option>
                @foreach($locations as $l)
                    <option value="{{ $l->id }}" @selected((string) ($filters['destination_id'] ?? '') === (string) $l->id)>{{ $l->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-sort">Sort by</label>
            <select id="f-sort" class="sh-sel" name="sort">
                @foreach($sortOpts as $k => $v)
                    <option value="{{ $k }}" @selected(($filters['sort'] ?? 'created_at') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-dir">Direction</label>
            <select id="f-dir" class="sh-sel" name="dir">
                <option value="desc" @selected($dir === 'desc')>Newest → Oldest</option>
                <option value="asc" @selected($dir === 'asc')>Oldest → Newest</option>
            </select>
        </div>
    </div>

    <div class="sh-row sh-row-3">
        <div>
            <label class="sh-lbl" for="f-from">Expected arrival from</label>
            <input id="f-from" class="sh-date" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label class="sh-lbl" for="f-to">Expected arrival to</label>
            <input id="f-to" class="sh-date" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <button class="sh-btn sh-btn-apply" type="submit">
            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
            <span>Apply filters</span>
        </button>
        <a class="sh-btn sh-btn-reset" href="{{ route('shipments.index') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
            <span>Reset</span>
        </a>
    </div>
</form>

{{-- Tabel data --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-table-head">
        <h2>
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ico['box'] }}"/></svg></span>
            <span>Shipment data</span>
            <small>(Showing {{ $from }}–{{ $to }} of {{ $total }} records)</small>
        </h2>
        <div class="sh-per">
            <label for="f-per">Show</label>:
            <select id="f-per" class="sh-sel" name="per_page" form="shipFilter" onchange="this.form.submit()">
                @foreach([10, 25, 50] as $n)
                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>
                @endforeach
            </select>
            @if($shipments->onFirstPage())
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></span>
            @else
                <a class="sh-arrow" href="{{ $shipments->previousPageUrl() }}" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></a>
            @endif
            @if($shipments->hasMorePages())
                <a class="sh-arrow" href="{{ $shipments->nextPageUrl() }}" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>
            @else
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
    </div>

    @include('partials.shipment-table', ['rows' => $shipments, 'startNo' => $shipments->firstItem() ?? 1])

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $shipments])
    </div>
</div>
@endsection

@push('scripts')
@include('partials.sh-scripts')
@endpush
