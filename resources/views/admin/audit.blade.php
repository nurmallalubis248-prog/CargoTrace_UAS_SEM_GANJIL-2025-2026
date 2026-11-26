@extends('layouts.app')
@section('title', 'Audit log')

@push('head')
@include('partials.sh-styles')
@include('partials.ct-admin-styles')
<style>
    /* ===== Khusus halaman Log Audit ===== */
    .au-filter, .au-filter * { box-sizing: border-box; }
    .au-row { display: grid; gap: 1rem 1.1rem; grid-template-columns: 1fr; align-items: end; }
    .au-row > * { min-width: 0; }
    @media (min-width: 768px)  { .au-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .au-row { grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; } }
    .au-btns { display: flex; flex-wrap: wrap; gap: .8rem; }
    .au-btns .sh-btn { height: 2.9rem; text-decoration: none; }

    .au-tbl { min-width: 60rem; }
    .au-tbl tbody td { vertical-align: middle; }
    .au-when { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .au-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .85rem .35rem .6rem; border-radius: 9999px; font-size: .8rem; font-weight: 700; line-height: 1.1; white-space: nowrap; }
    .au-badge svg { width: 1rem; height: 1rem; flex: none; }
    .au-add { background: #FFE3EE; color: #E91E8C; }
    .au-upd { background: #DDF3C8; color: #2F6B12; }
    .au-trk { background: #D6EBFF; color: #1D5FA8; }
    .au-del { background: #FFEBD0; color: #B45309; }
    .au-oth { background: #ECE6FF; color: #5B3FA0; }
    .au-sum { max-width: 24rem; font-size: .85rem; line-height: 1.35; color: #2B3027; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; word-break: break-word; }
    .au-act { display: flex; justify-content: center; }
    .au-act .ct-act-detail { padding: .45rem 1rem; white-space: nowrap; }
    .au-pre { margin: 0; max-height: 18rem; overflow: auto; padding: .9rem 1rem; border-radius: 1rem; background: #FFF7FA; border: 1px solid #F6D2E1; font-size: .8rem; line-height: 1.5; color: #2B3027; white-space: pre-wrap; word-break: break-word; }
    .au-per { display: flex; align-items: center; gap: .6rem; }
</style>
@endpush

@section('content')
@php
    $total = $logs->total();
    $from = $total ? $logs->firstItem() : 0;
    $to = $total ? $logs->lastItem() : 0;
    $perPage = $filters['per_page'];
    $docClock = 'M19.5 14.25v-2.63a3.38 3.38 0 0 0-3.38-3.37h-1.5A1.13 1.13 0 0 1 13.5 7.13v-1.5a3.38 3.38 0 0 0-3.38-3.38H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.63c-.62 0-1.13.5-1.13 1.13v17.25c0 .62.5 1.12 1.13 1.12h5.12M10.5 2.25a9 9 0 0 1 9 9';
    $ico = [
        'add' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        'upd' => '<path d="m16.9 3.6 3.5 3.5L8 19.5 3.5 20.5 4.5 16Z"/>',
        'trk' => '<path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.38a1.13 1.13 0 0 1-1.13-1.13V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.13c.62 0 1.12-.5 1.12-1.13V14.25m-17.25 0V6.38c0-.62.5-1.13 1.13-1.13h9.74c.62 0 1.13.5 1.13 1.13v11.62m0-11.62h2.9c.3 0 .58.12.8.33l3.3 3.3c.2.2.33.5.33.8v3.62"/>',
        'del' => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/>',
        'oth' => '<path d="M15 4h5v16h-5M10 8l-4 4 4 4M6 12h10"/>',
    ];
    // Jenis aktivitas dari akhiran nama aksi (mis. shipment.created -> add)
    $kind = function (string $action): string {
        $verb = strtolower(substr(strrchr('.'.$action, '.'), 1));
        return match (true) {
            in_array($verb, ['created', 'registered_staff', 'opened'], true) => 'add',
            in_array($verb, ['updated', 'role_changed', 'toggled'], true) => 'upd',
            in_array($verb, ['added'], true) => 'trk',
            in_array($verb, ['deleted'], true) => 'del',
            default => 'oth',
        };
    };
    $summary = function ($changes): string {
        if (! is_array($changes) || ! $changes) { return ''; }
        return collect($changes)->map(fn ($v, $k) => $k.': '.(is_scalar($v) || $v === null ? (is_bool($v) ? ($v ? 'true' : 'false') : (string) $v) : json_encode($v, JSON_UNESCAPED_UNICODE)))->implode(', ');
    };
@endphp

{{-- Kepala halaman --}}
<section class="ct-hero">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $docClock }}"/><circle cx="16.5" cy="16.5" r="4.5"/><path d="M16.5 14.5v2l1.4.9"/></svg></span>
        <div>
            <h1 data-i18n="audit">Audit log</h1>
            <p>Record of activity and data changes in the CargoTrace system.</p>
        </div>
    </div>

    <div class="ct-hero-art" aria-hidden="true">
        <svg viewBox="0 0 700 200" preserveAspectRatio="xMaxYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="auHill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#CDEBA0"/><stop offset="1" stop-color="#A9DC74"/></linearGradient>
                <linearGradient id="auShield" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF8DBF"/><stop offset="1" stop-color="#F0508F"/></linearGradient>
            </defs>
            <path d="M80 200 C180 150 300 150 400 170 C500 142 600 142 700 162 V200Z" fill="url(#auHill)"/>
            <path d="M240 200 C330 178 450 180 540 194 V200Z" fill="#B9E67F" opacity=".8"/>
            {{-- laptop --}}
            <g transform="translate(250 34)">
                <rect x="10" y="0" width="190" height="118" rx="10" fill="#EEF1F4" stroke="#C9D2D9" stroke-width="3"/>
                <rect x="20" y="10" width="170" height="98" rx="4" fill="#F4FBEF"/>
                <path d="M105 28 76 40v22c0 15 12 27 29 33 17-6 29-18 29-33V40L105 28Z" fill="#6DB83D"/><path d="m92 62 10 10 18-20" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M0 124 H210 L222 140 H-12Z" fill="#FFB6D5"/><rect x="80" y="126" width="50" height="5" rx="2.5" fill="#F58DB8"/>
            </g>
            {{-- perisai pink --}}
            <g transform="translate(500 54)"><path d="M44 0 0 16v38c0 28 18 48 44 58 26-10 44-30 44-58V16L44 0Z" fill="url(#auShield)"/><path d="m24 54 15 15 28-32" stroke="#fff" stroke-width="8" fill="none" stroke-linecap="round" stroke-linejoin="round"/></g>
            {{-- tanaman --}}
            <g><rect x="640" y="112" width="6" height="52" fill="#A87858"/><circle cx="643" cy="96" r="26" fill="#8FCF5E"/><circle cx="627" cy="114" r="16" fill="#A5DB72"/><circle cx="660" cy="114" r="16" fill="#7CC04F"/></g>
            <path d="M200 110 C186 86 196 66 222 60 C228 84 218 102 200 110Z" fill="#8FCF5E"/>
            <path d="M170 56 C160 40 170 26 188 22 C192 38 184 52 170 56Z" fill="#FFB6D5"/>
        </svg>
    </div>
</section>

{{-- Kartu filter --}}
<form method="GET" action="{{ route('admin.audit.index') }}" class="sh-panel au-filter">
    <div class="au-row">
        <div><label class="sh-lbl" for="f-from">Date from</label><input id="f-from" class="sh-fld" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
        <div><label class="sh-lbl" for="f-to">Date to</label><input id="f-to" class="sh-fld" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
        <div>
            <label class="sh-lbl" for="f-actor">Actor</label>
            <select id="f-actor" class="sh-sel" name="actor">
                <option value="">All</option>
                <option value="system" @selected($filters['actor'] === 'system')>System</option>
                @foreach($actors as $a)<option value="{{ $a->id }}" data-no-i18n @selected($filters['actor'] === (string) $a->id)>{{ $a->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-action">Activity</label>
            <select id="f-action" class="sh-sel" name="action">
                <option value="">All</option>
                @foreach($actions as $a)<option value="{{ $a }}" data-no-i18n @selected($filters['action'] === $a)>{{ $a }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-entity">Entity</label>
            <select id="f-entity" class="sh-sel" name="entity">
                <option value="">All</option>
                @foreach($entities as $e)<option value="{{ $e }}" data-no-i18n @selected($filters['entity'] === $e)>{{ $e }}</option>@endforeach
            </select>
        </div>
        <div class="au-btns">
            <button class="sh-btn sh-btn-apply" type="submit">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
                <span>Apply Filters</span>
            </button>
            <a class="sh-btn sh-btn-reset" href="{{ route('admin.audit.index') }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
                <span>Reset Filters</span>
            </a>
        </div>
    </div>
</form>

{{-- Tabel data --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-tbl-wrap">
        <table class="sh-tbl ct-tbl au-tbl">
            <thead>
                <tr>
                    <th style="width:4rem">No.</th>
                    <th>When (UTC)</th>
                    <th>Actor</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th class="ct-c"></th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $i => $log)
                @php $k = $kind($log->action); $sum = $summary($log->changes); @endphp
                <tr>
                    <td>{{ $logs->firstItem() + $i }}</td>
                    <td class="au-when" data-no-i18n>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td data-no-i18n>@if($log->actor){{ $log->actor->name }}@else System @endif</td>
                    <td data-no-i18n><span class="au-badge au-{{ $k }}"><svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $ico[$k] !!}</svg><span>{{ $log->action }}</span></span></td>
                    <td data-no-i18n>{{ $log->entity_type }}@if($log->entity_id) #{{ $log->entity_id }}@endif</td>
                    <td><div class="au-sum" data-no-i18n>{{ $sum !== '' ? $sum : '-' }}</div></td>
                    <td>
                        <div class="au-act"
                             data-when="{{ $log->created_at->format('Y-m-d H:i:s') }}" data-actor="{{ $log->actor?->name ?? 'System' }}"
                             data-action="{{ $log->action }}" data-entity="{{ $log->entity_type }}{{ $log->entity_id ? ' #'.$log->entity_id : '' }}"
                             data-ip="{{ $log->ip_address ?? '-' }}"
                             data-json="{{ $log->changes ? json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '' }}">
                            <button type="button" class="ct-act ct-act-detail" data-act="detail">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>View detail</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="sh-empty">No audit entries.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        <div class="au-per">
            <select id="f-per" class="sh-sel" style="width:auto;min-width:6.6rem;height:2.4rem;border-radius:.8rem" aria-label="Show" onchange="var u=new URL(location.href);u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location.href=u.toString()">
                @foreach([10, 25, 50] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>@endforeach
            </select>
            @include('partials.pager', ['paginator' => $logs])
        </div>
    </div>
</div>

{{-- Dialog: detail --}}
<dialog id="dlg-detail" class="ct-dlg">
    <div class="ct-dlg-h">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span>
        <h3>Audit log detail</h3>
        <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="ct-dlg-b">
        <dl class="ct-dl">
            <dt>When (UTC)</dt><dd id="dt-when" data-no-i18n></dd>
            <dt>Actor</dt><dd id="dt-actor" data-no-i18n></dd>
            <dt>Action</dt><dd id="dt-action" data-no-i18n></dd>
            <dt>Entity</dt><dd id="dt-entity" data-no-i18n></dd>
            <dt>IP address</dt><dd id="dt-ip" data-no-i18n></dd>
        </dl>
        <div><label class="sh-lbl">Details</label><pre class="au-pre" id="dt-json" data-no-i18n></pre></div>
    </div>
    <div class="ct-dlg-f"><button type="button" class="sh-btn sh-btn-apply" data-close>Close</button></div>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    document.querySelectorAll('.au-act [data-act="detail"]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = b.closest('.au-act').dataset;
            $('dt-when').textContent = d.when; $('dt-actor').textContent = d.actor; $('dt-action').textContent = d.action;
            $('dt-entity').textContent = d.entity; $('dt-ip').textContent = d.ip; $('dt-json').textContent = d.json || '-';
            $('dlg-detail').showModal();
        });
    });
    document.querySelectorAll('dialog.ct-dlg').forEach(function (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) { dlg.close(); } });
    });
})();
</script>
@endpush