@extends('layouts.app')
@section('title', 'Notifications')

@push('head')
@include('partials.sh-styles')
<style>
    .nt-tabs { display: flex; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; border-bottom: 1px solid #F6D9E6; margin: -.4rem -.5rem 1rem; padding: 0 .5rem; }
    .nt-tablist { display: flex; gap: .35rem; flex-wrap: wrap; }
    .nt-tab { display: inline-flex; align-items: center; gap: .55rem; padding: .8rem 1.5rem; border-radius: 1rem 1rem 0 0; font-weight: 800; font-size: .88rem; color: #2B3027; background: rgba(255,255,255,.6); transition: background .15s; }
    .nt-tab svg { width: 1.1rem; height: 1.1rem; }
    .nt-tab:hover { background: #FFF0F6; }
    .nt-tab.on { color: #fff; background: linear-gradient(90deg, #FF6FAE, #F0508F); box-shadow: 0 10px 20px -10px rgba(233,30,140,.7); }
    .nt-readall { display: inline-flex; align-items: center; gap: .55rem; margin-bottom: .6rem; padding: .6rem 1.3rem; border-radius: 9999px; background: #fff; border: 1.5px solid #86A85D; color: #324422; font-weight: 800; font-size: .82rem; cursor: pointer; transition: background .15s; }
    .nt-readall:hover { background: #F3F7EE; }
    .nt-readall svg { width: 1.1rem; height: 1.1rem; }

    .nt-list { display: flex; flex-direction: column; }
    .nt-item { display: flex; align-items: center; gap: 1.2rem; padding: 1rem 1.3rem; border-bottom: 1px solid #F3E4EB; border-radius: 1.1rem; color: inherit; transition: background .15s; }
    .nt-item:last-child { border-bottom: 0; }
    a.nt-item:hover { background: rgba(255,227,238,.45); }
    .nt-item.is-new { background: linear-gradient(90deg, rgba(255,227,238,.85), rgba(255,240,246,.7)); }
    .nt-ico { display: grid; place-items: center; width: 4rem; height: 4rem; flex: none; border-radius: 9999px; color: #fff; box-shadow: 0 8px 18px -6px rgba(0,0,0,.3); }
    .nt-ico svg { width: 1.9rem; height: 1.9rem; }
    .nt-c-rose   { background: linear-gradient(135deg, #FF7DB5, #F0407F); }
    .nt-c-green  { background: linear-gradient(135deg, #3FBF6B, #1F9A4C); }
    .nt-c-blue   { background: linear-gradient(135deg, #5B8DF5, #2F63D6); }
    .nt-c-orange { background: linear-gradient(135deg, #FFB547, #F08A12); }
    .nt-main { min-width: 0; flex: 1; }
    .nt-chip { display: inline-block; padding: .12rem .6rem; border-radius: .45rem; font-size: .7rem; font-weight: 700; }
    .nt-chip-rose { background: #FFE0EC; color: #C81E6F; } .nt-chip-green { background: #DDF3C8; color: #2F6B12; }
    .nt-chip-blue { background: #DCEBFF; color: #1D5FA8; } .nt-chip-orange { background: #FFEBD0; color: #B45309; }
    .nt-title { margin-top: .3rem; font-size: 1rem; font-weight: 800; color: #1B1F18; line-height: 1.3; overflow-wrap: anywhere; }
    .nt-desc { margin-top: .15rem; font-size: .86rem; color: #565E4E; }
    .nt-time { margin-top: .25rem; display: flex; align-items: center; gap: .4rem; font-size: .78rem; color: #565E4E; }
    .nt-time svg { width: .9rem; height: .9rem; flex: none; }
    .nt-pill { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .9rem; border-radius: 9999px; font-size: .76rem; font-weight: 700; white-space: nowrap; box-shadow: 0 4px 10px -6px rgba(0,0,0,.25); }
    .nt-pill i { width: .7rem; height: .7rem; border-radius: 9999px; flex: none; }
    .nt-pill svg { width: .95rem; height: .95rem; flex: none; }
    .nt-p-new { background: #FFE4EE; color: #C81E6F; } .nt-p-new i { background: #F0407F; }
    .nt-p-unread { background: #E6EEFF; color: #1D4FB0; } .nt-p-unread i { background: #2F63D6; }
    .nt-p-read { background: #E3F4CC; color: #2F6B12; }
    .nt-side { display: flex; align-items: center; gap: 1rem; flex: none; }
    .nt-arrow { width: 1.4rem; height: 1.4rem; color: #3F463A; }
    .nt-item .nt-arrow { opacity: .75; }
    @media (max-width: 640px) { .nt-item { align-items: flex-start; gap: .8rem; padding: .9rem .6rem; } .nt-ico { width: 3rem; height: 3rem; } .nt-ico svg { width: 1.5rem; height: 1.5rem; } .nt-side { flex-direction: column; align-items: flex-end; gap: .4rem; } .nt-tab { padding: .7rem 1rem; } }
</style>
@endpush

@section('content')
@php
    $total = $notifications->total();
    $from = $total ? $notifications->firstItem() : 0;
    $to = $total ? $notifications->lastItem() : 0;

    $svg = [
        'bell'  => 'M14.86 17.08a23.85 23.85 0 0 0 5.45-1.31A8.97 8.97 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.97 8.97 0 0 1-2.31 6.02 23.85 23.85 0 0 0 5.45 1.31m5.72 0a24.26 24.26 0 0 1-5.72 0m5.72 0a3 3 0 1 1-5.72 0',
        'box'   => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'plane' => 'M21 3 3 10.5l6.5 2.5L12 20l2.5-5.5L21 3Zm-11.5 10L21 3',
        'check' => 'm5 12.5 4.5 4.5L19 7.5',
        'doc'   => 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm0 0v5h5M9 13h6M9 17h6',
        'warn'  => 'M12 9v4m0 4h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v5l3 2',
        'chev'  => 'm9 18 6-6-6-6',
    ];
    // tipe notifikasi => [chip, warna, ikon, deskripsi (kunci kamus, {no} = nomor pengiriman)]
    $types = [
        'SHIPMENT_CREATED'   => ['Shipment', 'rose', 'box', 'Shipment %s has been created and is ready for processing.'],
        'STATUS_CHANGED'     => ['Shipment', 'green', 'plane', 'The status of shipment %s has been updated.'],
        'DELIVERY_CONFIRMED' => ['Shipment', 'green', 'check', 'Shipment %s was received by the customer.'],
        'SHIPMENT_DELAYED'   => ['Shipment', 'orange', 'warn', 'Shipment %s is delayed. Check the details.'],
        'EXCEPTION_OPENED'   => ['Exception', 'orange', 'warn', 'A new exception was reported on shipment %s.'],
        'EXCEPTION_RESOLVED' => ['Exception', 'green', 'check', 'The exception on shipment %s has been resolved.'],
        'PAYMENT_RECEIVED'   => ['Payment', 'blue', 'doc', 'Payment for shipment %s was received from the customer.'],
        'PAYMENT_CONFIRMED'  => ['Payment', 'blue', 'doc', 'Payment for shipment %s was confirmed.'],
    ];
    $tabs = [['all', 'All', 'bell'], ['unread', 'Unread', 'bell'], ['read', 'Read', 'check']];
@endphp

{{-- Kepala halaman --}}
<div class="sh-head">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $svg['bell'] }}"/></svg></span>
        <div>
            <h1 data-i18n="notifications">Notifications</h1>
            <p>Manage and monitor all the latest CargoTrace notifications</p>
        </div>
    </div>
</div>

<div class="sh-panel">
    <div class="nt-tabs">
        <div class="nt-tablist" role="tablist">
            @foreach($tabs as [$key, $label, $icon])
                <a href="{{ route('notifications.index', $key === 'all' ? [] : ['tab' => $key]) }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}" class="nt-tab {{ $tab === $key ? 'on' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $svg[$icon] }}"/></svg>
                    <span>{{ $label }}</span>@if($key !== 'read')<span data-no-i18n>({{ $counts[$key] }})</span>@endif
                </a>
            @endforeach
        </div>
        @if($counts['unread'] > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
                <button class="nt-readall" type="submit">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $svg['check'] }}"/></svg>
                    <span>Mark all as read</span>
                </button>
            </form>
        @endif
    </div>

    <div class="nt-list">
        @forelse($notifications as $n)
            @php
                [$chip, $color, $icon, $descTpl] = $types[$n->type] ?? ['Shipment', 'rose', 'bell', null];
                $no = $n->shipment?->shipment_number;
                $isUnread = $n->read_at === null;
                $isNew = $isUnread && $n->created_at->gt(now()->subDay());
                $ts = $n->created_at->copy()->utc();
                $tag = $n->shipment ? 'a' : 'div';
            @endphp
            <{{ $tag }} @if($n->shipment) href="{{ route('shipments.show', $n->shipment) }}" @endif class="nt-item {{ $isNew ? 'is-new' : '' }}">
                <span class="nt-ico nt-c-{{ $color }}"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $svg[$icon] }}"/>@if($icon === 'box')<path d="m7.5 5 9 5.2" stroke-width="1.2"/>@endif</svg></span>
                <div class="nt-main">
                    <span class="nt-chip nt-chip-{{ $color }}">{{ $chip }}</span>
                    <p class="nt-title">{{ $n->message }}</p>
                    @if($descTpl && $no)<p class="nt-desc">{{ sprintf($descTpl, $no) }}</p>@endif
                    <p class="nt-time" data-no-i18n>
                        <svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $svg['clock'] }}"/></svg>
                        <span data-ts="{{ $ts->toIso8601String() }}">{{ $ts->format('d M Y • H:i') }} (UTC)</span>
                    </p>
                </div>
                <div class="nt-side">
                    @if($isNew)
                        <span class="nt-pill nt-p-new"><i></i><span>New</span></span>
                    @elseif($isUnread)
                        <span class="nt-pill nt-p-unread"><i></i><span>Unread</span></span>
                    @else
                        <span class="nt-pill nt-p-read"><svg fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="#22B14C" stroke="none"/><path d="m8 12.5 3 3 5-6" stroke="#fff"/></svg><span>Read</span></span>
                    @endif
                    @if($n->shipment)<svg class="nt-arrow" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $svg['chev'] }}"/></svg>@endif
                </div>
            </{{ $tag }}>
        @empty
            <p class="sh-empty">No notifications yet.</p>
        @endforelse
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $notifications])
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Waktu mengikuti negara/zona waktu yang dipilih di topbar (data tersimpan dalam UTC)
    function paint() {
        if (!window.CT || !CT.dateStr) { return; }
        var m = (CT.tzLabel().match(/\(([^)]*)\)\s*$/) || [])[1] || 'UTC';
        document.querySelectorAll('[data-ts]').forEach(function (el) {
            var d = new Date(el.dataset.ts); if (isNaN(d)) { return; }
            var date = CT.dateStr(d, { day: 'numeric', month: 'short', year: 'numeric' });
            var time = d.toLocaleTimeString(CT.locale(), { hour: '2-digit', minute: '2-digit', hourCycle: 'h23', timeZone: CT.tz() });
            el.textContent = date + ' • ' + time + ' (' + m + ')';
        });
    }
    document.addEventListener('ct:country', paint);
    document.addEventListener('ct:lang', paint);
    document.addEventListener('DOMContentLoaded', paint);
    paint();
})();
</script>
@endpush
