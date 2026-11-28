@extends('layouts.app')
@section('title', 'Reports')

@push('head')
@include('partials.sh-styles')
<style>
    .rp-filter { position: relative; overflow: hidden; }
    @media (min-width: 1280px) { .rp-filter { padding-right: 15rem; } }
    .rp-deco { position: absolute; right: 1.2rem; top: .6rem; width: 13rem; height: 8.5rem; opacity: .9; pointer-events: none; display: none; }
    @media (min-width: 1280px) { .rp-deco { display: block; } }

    .rp-kpis { display: grid; gap: 1rem; grid-template-columns: repeat(1, minmax(0, 1fr)); margin-top: 1.1rem; }
    @media (min-width: 640px)  { .rp-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1280px) { .rp-kpis { grid-template-columns: repeat(12, minmax(0, 1fr)); } .rp-kpi { grid-column: span 3; } .rp-kpi:nth-child(n+5) { grid-column: span 4; } }
    .rp-kpi { position: relative; overflow: hidden; display: flex; align-items: center; gap: 1rem; min-height: 6.6rem; padding: 1.1rem 1.3rem; border-radius: 22px; border: 1px solid rgba(255,255,255,.95); box-shadow: 0 10px 28px -14px rgba(233,30,140,.3); }
    .rp-kpi.t-pink  { background: linear-gradient(135deg, rgba(255,255,255,.92), rgba(255,225,240,.9)); }
    .rp-kpi.t-green { background: linear-gradient(135deg, rgba(255,255,255,.92), rgba(228,246,205,.9)); }
    .rp-ico { display: grid; place-items: center; width: 3.6rem; height: 3.6rem; flex: none; border-radius: 9999px; color: #fff; box-shadow: 0 8px 18px -6px rgba(0,0,0,.28); }
    .rp-ico svg { width: 1.8rem; height: 1.8rem; }
    .rp-pink  { background: linear-gradient(135deg, #FF7DB5, #F0407F); }
    .rp-green { background: linear-gradient(135deg, #A8DB6A, #6DB43A); }
    .rp-body { min-width: 0; position: relative; z-index: 1; }
    .rp-lbl { font-size: .86rem; font-weight: 800; color: #2B3027; line-height: 1.2; }
    .rp-val { margin-top: .15rem; font-size: 2.1rem; font-weight: 800; line-height: 1.1; color: #1B1F18; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .rp-val-money { font-size: clamp(1.4rem, 1rem + 1vw, 2rem); }
    .rp-spark { position: absolute; right: .9rem; bottom: .6rem; width: 42%; height: 52%; pointer-events: none; }
    .rp-note { margin-top: .6rem; font-size: .78rem; color: #565E4E; }
</style>
@endpush

@section('content')
@php
    $s = $report['summary'];
    $user = auth()->user();
    $dropKeys = ['lang', 'page', 'per_page'];
    $pdfUrl = route('reports.pdf', array_filter(\Illuminate\Support\Arr::except($filters, $dropKeys), fn ($v) => $v !== null && $v !== ''));

    // Halaman data (laporan memuat maks. MAX_ROWS baris, dipaginasi di sini)
    $allRows = $report['rows']->values();
    $perReq = (int) ($filters['per_page'] ?? 10);
    $perPage = in_array($perReq, [10, 25, 50], true) ? $perReq : 10;
    $page = max(1, \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage());
    $pager = (new \Illuminate\Pagination\LengthAwarePaginator($allRows->forPage($page, $perPage)->values(), $allRows->count(), $perPage, $page, ['path' => route('reports.index')]))->withQueryString();
    $total = $pager->total();
    $from = $total ? $pager->firstItem() : 0;
    $to = $total ? $pager->lastItem() : 0;

    // Garis kecil di kartu: jumlah per hari (tanggal dibuat) 14 hari terakhir, dari baris laporan
    $days = collect(range(13, 0))->map(fn ($d) => now('UTC')->subDays($d)->toDateString());
    $spark = function (callable $pred) use ($allRows, $days) {
        $arr = $days->map(fn ($day) => $allRows->filter(fn ($r) => $r->created_at && $r->created_at->copy()->utc()->toDateString() === $day && $pred($r))->count())->all();
        $max = max($arr);
        if ($max <= 0) { return ''; }
        $step = 100 / (count($arr) - 1);
        return collect($arr)->map(fn ($x, $i) => number_format($i * $step, 1, '.', '').','.number_format(36 - ($x / $max) * 32, 1, '.', ''))->implode(' ');
    };
    $openExc = fn ($r) => $r->exceptions->contains(fn ($e) => $e->resolved_at === null);

    $ic = [
        'box' => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'plane' => 'M21 3 3 10.5l6.5 2.5L12 20l2.5-5.5L21 3Zm-11.5 10L21 3',
        'check' => 'm5 12.5 4.5 4.5L19 7.5',
        'warn' => 'M12 9v4m0 4h.01M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z',
        'alert' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v4m0 4h.01',
        'pct' => 'M19 5 5 19M7.5 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z',
        'card' => 'M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 16.5v-9Zm0 3h18M7 15h3',
    ];
    // [label, nilai, ikon, warna, garis kecil]
    $cards = [
        ['Total shipments', $s['total'], 'box', 'pink', $spark(fn ($r) => true)],
        ['In transit', $s['in_transit'], 'plane', 'green', $spark(fn ($r) => $r->current_status->value === 'IN_TRANSIT')],
        ['Delivered', $s['delivered'], 'check', 'pink', $spark(fn ($r) => $r->current_status->value === 'DELIVERED')],
        ['Delayed', $s['delayed'], 'warn', 'green', $spark(fn ($r) => $r->is_delayed && $r->current_status->value !== 'CANCELLED')],
        ['Open exceptions', $s['open_exceptions'], 'alert', 'pink', $spark($openExc)],
        ['Delay rate', $s['delay_rate_pct'].'%', 'pct', 'green', ''],
    ];
@endphp

{{-- Kepala halaman --}}
<div class="sh-head">
    <div class="sh-title">
        <span class="ico"><svg fill="currentColor" viewBox="0 0 24 24"><rect x="3.5" y="12" width="4.2" height="8.5" rx="1.2"/><rect x="9.9" y="5" width="4.2" height="15.5" rx="1.2"/><rect x="16.3" y="9" width="4.2" height="11.5" rx="1.2"/></svg></span>
        <div>
            <h1 data-i18n="reports">Reports</h1>
            <p>Choose filters, check the report data, then download it as a PDF.</p>
        </div>
    </div>
    @writable
    <a id="pdf-btn" href="{{ $pdfUrl }}" class="sh-new">
        <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
        <span>Download PDF</span>
    </a>
    @endwritable
</div>

{{-- Kartu filter --}}
<form id="repFilter" method="GET" action="{{ route('reports.index') }}" class="sh-panel sh-filters rp-filter">
    <svg class="rp-deco" viewBox="0 0 208 136" fill="none" aria-hidden="true">
        <g fill="#F3C9DA" opacity=".55"><ellipse cx="40" cy="48" rx="30" ry="18"/><ellipse cx="86" cy="70" rx="22" ry="26"/><ellipse cx="136" cy="44" rx="38" ry="20"/><ellipse cx="164" cy="86" rx="22" ry="14"/></g>
        <path d="M40 76 C70 20 120 30 150 100" stroke="#F0508F" stroke-width="1.6" stroke-dasharray="4 5" stroke-linecap="round"/>
        <g fill="#F0508F"><path d="M28 36a9 9 0 1 1 18 0c0 8-9 17-9 17s-9-9-9-17Z"/><path d="M162 60a9 9 0 1 1 18 0c0 8-9 17-9 17s-9-9-9-17Z"/><path d="M116 12a8 8 0 1 1 16 0c0 7-8 15-8 15s-8-8-8-15Z"/></g>
        <g fill="#fff"><circle cx="37" cy="36" r="3"/><circle cx="171" cy="60" r="3"/><circle cx="124" cy="12" r="2.6"/></g>
    </svg>

    <div class="sh-row sh-row-4">
        <div>
            <label class="sh-lbl" for="r-status">Status</label>
            <select id="r-status" class="sh-sel" name="status">
                <option value="">All</option>
                @foreach(\App\Enums\ShipmentStatus::cases() as $st)
                    <option value="{{ $st->value }}" @selected(($filters['status'] ?? '') === $st->value)>{{ ucwords(strtolower(str_replace('_', ' ', $st->value))) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="r-delayed">Delay</label>
            <select id="r-delayed" class="sh-sel" name="delayed">
                <option value="">All</option>
                <option value="1" @selected(($filters['delayed'] ?? '') === '1')>Delayed</option>
                <option value="0" @selected(($filters['delayed'] ?? '') === '0')>On time</option>
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="r-exc">Exception</label>
            <select id="r-exc" class="sh-sel" name="exception">
                <option value="">All</option>
                <option value="open" @selected(($filters['exception'] ?? '') === 'open')>Open exceptions</option>
                <option value="any" @selected(($filters['exception'] ?? '') === 'any')>Any exception</option>
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="r-carrier">Carrier</label>
            <select id="r-carrier" class="sh-sel" name="carrier_id">
                <option value="">All</option>
                @foreach($carriers as $c)
                    <option value="{{ $c->id }}" @selected((string) ($filters['carrier_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="sh-row sh-row-3">
        <div>
            <label class="sh-lbl" for="r-from">Expected arrival from</label>
            <input id="r-from" class="sh-date" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label class="sh-lbl" for="r-to">Expected arrival to</label>
            <input id="r-to" class="sh-date" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <button class="sh-btn sh-btn-apply" type="submit">
            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
            <span>Apply filters</span>
        </button>
        <a class="sh-btn sh-btn-reset" href="{{ route('reports.index') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
            <span>Reset</span>
        </a>
    </div>
</form>

{{-- Kartu ringkasan --}}
<div class="rp-kpis">
    @foreach($cards as [$label, $value, $icon, $tone, $pts])
        <div class="rp-kpi t-{{ $tone }}">
            <span class="rp-ico rp-{{ $tone }}"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic[$icon] }}"/>@if($icon === 'box')<path d="m7.5 5 9 5.2" stroke-width="1.2"/>@endif</svg></span>
            <div class="rp-body">
                <p class="rp-lbl">{{ $label }}</p>
                <p class="rp-val">{{ $value }}</p>
            </div>
            @if($pts)
                <svg class="rp-spark" viewBox="0 0 100 40" preserveAspectRatio="none" aria-hidden="true"><polyline fill="none" stroke="{{ $tone === 'green' ? '#6DB43A' : '#F0508F' }}" stroke-width="1.8" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" points="{{ $pts }}"/></svg>
            @endif
        </div>
    @endforeach
</div>

{{-- Tabel data laporan --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-table-head">
        <h2>
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm0 0v5h5M9 13h6M9 17h6"/></svg></span>
            <span>Report data</span>
        </h2>
        <div class="sh-per">
            <label for="r-per">Show</label>:
            <select id="r-per" class="sh-sel" name="per_page" form="repFilter" onchange="this.form.submit()">
                @foreach([10, 25, 50] as $n)
                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>
                @endforeach
            </select>
            @if($pager->onFirstPage())
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></span>
            @else
                <a class="sh-arrow" href="{{ $pager->previousPageUrl() }}" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></a>
            @endif
            @if($pager->hasMorePages())
                <a class="sh-arrow" href="{{ $pager->nextPageUrl() }}" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>
            @else
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
    </div>

    @include('partials.shipment-table', ['rows' => $pager, 'startNo' => $pager->firstItem() ?? 1])

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $pager])
    </div>
    @if($report['total'] > \App\Services\ReportService::MAX_ROWS)
        <p class="rp-note">Only the first {{ \App\Services\ReportService::MAX_ROWS }} of {{ $report['total'] }} shipments are shown and included in the PDF.</p>
    @endif
</div>
@endsection

@push('scripts')
@include('partials.sh-scripts')
<script>
    // Bahasa dipilih di browser, jadi beri tahu server bahasa untuk PDF.
    var pdfBtn = document.getElementById('pdf-btn');   // tidak ada di Mode Viewer
    if (pdfBtn) { pdfBtn.addEventListener('click', function () {
        let l = null; try { l = localStorage.getItem('ct_lang'); } catch (e) {}
        if (l === 'id' || l === 'en') { const u = new URL(this.href); u.searchParams.set('lang', l); this.href = u.toString(); }
    }); }
</script>
@endpush
