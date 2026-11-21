@extends('layouts.app')
@section('title', 'Accounting')

@push('head')
@include('partials.sh-styles')
@include('partials.ct-admin-styles')
<style>
    /* ===== Khusus halaman Akuntansi ===== */
    @media (min-width: 1280px) {
        .ct-hero { min-height: 14.5rem; }
        .ct-hero-art { align-self: start; margin-top: .4rem; margin-right: 1.5rem; max-width: 27rem; height: 10rem; }
        .ct-add { bottom: .7rem; }
    }
    a.ct-add { text-decoration: none; }

    /* Kartu statistik: ikon bulat + label + nilai (nilai tidak pernah terpotong / turun baris) */
    .ac-stats { display: grid; gap: 1rem; grid-template-columns: 1fr; margin-bottom: 1.1rem; }
    @media (min-width: 640px)  { .ac-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .ac-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .ac-card { display: flex; align-items: center; gap: 1rem; min-width: 0; padding: 1.1rem 1.2rem; border-radius: 22px; background: rgba(255,255,255,.88); border: 1px solid rgba(255,255,255,.95); box-shadow: 0 8px 28px -12px rgba(233,30,140,.22); }
    .ac-ico { flex: none; display: grid; place-items: center; width: 3.6rem; height: 3.6rem; border-radius: 9999px; color: #fff; background: linear-gradient(135deg, #FF8DBF, #F0508F); box-shadow: 0 10px 20px -10px rgba(233,30,140,.6); }
    .ac-ico.g { background: linear-gradient(135deg, #B5E27C, #6DB83D); box-shadow: 0 10px 20px -10px rgba(80,150,40,.55); }
    .ac-ico svg { width: 1.8rem; height: 1.8rem; }
    .ac-txt { min-width: 0; flex: 1; container-type: inline-size; }
    .ac-lbl { font-size: .88rem; font-weight: 600; color: #565E4E; line-height: 1.2; }
    .ac-card .ac-val { margin-top: .2rem; font-size: clamp(1rem, 9cqw, 1.55rem); font-weight: 800; line-height: 1.2; color: #1B1F18; white-space: nowrap; overflow-wrap: normal; font-variant-numeric: tabular-nums; }

    /* Filter */
    .ac-fh { display: flex; align-items: center; gap: .7rem; margin-bottom: 1rem; font-size: 1.1rem; font-weight: 800; color: #1B1F18; }
    .ac-fh .ico { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 9999px; background: #FFE3EE; color: #E91E8C; }
    .ac-fh .ico svg { width: 1.1rem; height: 1.1rem; }
    .ac-filter, .ac-filter * { box-sizing: border-box; }
    .ac-row { display: grid; gap: 1rem 1.1rem; grid-template-columns: 1fr; align-items: end; }
    .ac-row > * { min-width: 0; }
    @media (min-width: 768px)  { .ac-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .ac-row { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
    .ac-btns { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 1.1rem; }
    .ac-btns .sh-btn { height: 2.9rem; text-decoration: none; }

    /* Tabel */
    .ac-tbl { min-width: 62rem; }
    .ac-desc { font-weight: 600; color: #1B1F18; }
    .ac-sub { display: block; font-size: .76rem; font-weight: 500; color: #565E4E; }
    .ac-meth { display: inline-flex; align-items: center; gap: .45rem; font-weight: 600; }
    .ac-meth svg { width: 1.1rem; height: 1.1rem; color: #3A6FC4; flex: none; }
    .ac-pay { display: block; margin-top: .25rem; font-size: .7rem; font-weight: 700; color: #565E4E; }
    .ac-act { display: flex; align-items: center; justify-content: center; gap: .4rem; }
    .ac-act .ct-act-detail { padding: .45rem 1rem; }
    .ac-none { width: 2rem; }
    .sh-menu .ac-mi { display: block; width: 100%; text-align: left; padding: .55rem .8rem; border: 0; border-radius: .7rem; background: transparent; font-size: .85rem; font-weight: 700; color: #2B3027; cursor: pointer; text-decoration: none; }
    .sh-menu .ac-mi:hover { background: #FFF0F6; color: #E91E8C; }
    .ac-month { display: grid; gap: .5rem; }
    .ac-month div { display: flex; justify-content: space-between; gap: 1rem; padding: .6rem .9rem; border-radius: 1rem; background: #F7FBF0; font-weight: 600; }
    .ac-month b { font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')
@php
    $idr = fn ($v) => 'Rp '.number_format($v, 0, ',', '.');
    $total = $payments->total();
    $from = $total ? $payments->firstItem() : 0;
    $to = $total ? $payments->lastItem() : 0;
    $perPage = $filters['per_page'];
    $docIco = 'M19.5 14.25v-2.63a3.38 3.38 0 0 0-3.38-3.37h-1.5A1.13 1.13 0 0 1 13.5 7.13v-1.5a3.38 3.38 0 0 0-3.38-3.38H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.63c-.62 0-1.13.5-1.13 1.13v17.25c0 .62.5 1.12 1.13 1.12h12.75c.62 0 1.12-.5 1.12-1.12V11.25a9 9 0 0 0-9-9Z';
    $stat = [
        ['Revenue received', $revenue, 'M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3', '', true],
        ['Pending settlement', $pendingSettlement, 'M12 6v6l4 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'g', true],
        ['Settled', $settled, 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', '', true],
        ['Pending payments', $pending, 'M6 2.75h12M6 21.25h12M7 2.75c0 5 3 6 5 9.25-2 3.25-5 4.25-5 9.25M17 2.75c0 5-3 6-5 9.25 2 3.25 5 4.25 5 9.25', 'g', true],
        ['Failed / expired', $failed, 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', '', false],
        ['Transactions', $count, $docIco, 'g', false],
    ];
    $statusMap = [
        'PAID' => ['Paid', 'sh-b-green'], 'PENDING' => ['Pending', 'sh-b-orange'],
        'FAILED' => ['Failed', 'sh-b-red'], 'EXPIRED' => ['Expired', 'sh-b-grey'], 'CANCELLED' => ['Cancelled', 'sh-b-grey'],
    ];
    $bank = 'M3 21h18M5 21V10m4 11V10m6 11V10m4 11V10M3 10l9-6 9 6';
@endphp

{{-- Kepala halaman --}}
<section class="ct-hero">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $docIco }}"/><path d="M12.5 12.5v4m1.5-3.2c0-.6-.7-1-1.5-1s-1.5.4-1.5 1 .7.9 1.5 1 1.5.4 1.5 1-.7 1-1.5 1-1.5-.4-1.5-1"/></svg></span>
        <div>
            <h1>Accounting</h1>
            <p>Manage financial transaction data and accounting reports.</p>
        </div>
    </div>

    <div class="ct-hero-art" aria-hidden="true">
        <svg viewBox="0 0 700 200" preserveAspectRatio="xMaxYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="acHill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#CDEBA0"/><stop offset="1" stop-color="#A9DC74"/></linearGradient>
                <linearGradient id="acCalc" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF8DBF"/><stop offset="1" stop-color="#F0508F"/></linearGradient>
            </defs>
            <path d="M80 200 C180 150 300 150 400 170 C500 142 600 142 700 162 V200Z" fill="url(#acHill)"/>
            <path d="M240 200 C330 178 450 180 540 194 V200Z" fill="#B9E67F" opacity=".8"/>
            {{-- kalkulator --}}
            <g transform="translate(300 40) rotate(8 60 70)">
                <rect width="120" height="144" rx="16" fill="url(#acCalc)"/>
                <rect x="14" y="14" width="92" height="30" rx="7" fill="#FFE3EE"/>
                <g fill="#FFD3E6"><rect x="14" y="56" width="24" height="20" rx="5"/><rect x="48" y="56" width="24" height="20" rx="5"/><rect x="82" y="56" width="24" height="20" rx="5"/><rect x="14" y="86" width="24" height="20" rx="5"/><rect x="48" y="86" width="24" height="20" rx="5"/><rect x="82" y="86" width="24" height="20" rx="5"/><rect x="14" y="116" width="24" height="18" rx="5"/><rect x="48" y="116" width="24" height="18" rx="5"/></g>
                <rect x="82" y="116" width="24" height="18" rx="5" fill="#fff"/>
            </g>
            {{-- koin --}}
            <g transform="translate(220 126)">
                <ellipse cx="34" cy="34" rx="34" ry="10" fill="#E8B84A"/><rect x="0" y="22" width="68" height="12" fill="#F6D06B"/><ellipse cx="34" cy="22" rx="34" ry="10" fill="#FFE08A"/>
                <ellipse cx="34" cy="12" rx="34" ry="10" fill="#E8B84A"/><rect x="0" y="0" width="68" height="12" fill="#F6D06B"/><ellipse cx="34" cy="0" rx="34" ry="10" fill="#FFE08A"/>
                <ellipse cx="34" cy="-12" rx="34" ry="10" fill="#E8B84A"/><rect x="0" y="-24" width="68" height="12" fill="#F6D06B"/><ellipse cx="34" cy="-24" rx="34" ry="10" fill="#FFE08A"/>
            </g>
            {{-- rumah kecil --}}
            <g transform="translate(470 98)">
                <rect x="0" y="30" width="86" height="60" fill="#FFF4F9"/><path d="M-8 34 L43 4 L94 34Z" fill="#FF8DBF"/>
                <rect x="14" y="48" width="22" height="20" rx="2" fill="#CFEFFF"/><rect x="50" y="48" width="22" height="20" rx="2" fill="#CFEFFF"/><rect x="34" y="68" width="18" height="22" rx="2" fill="#FF8DBF"/>
            </g>
            {{-- tanaman --}}
            <g><rect x="598" y="116" width="6" height="48" fill="#A87858"/><circle cx="601" cy="100" r="26" fill="#8FCF5E"/><circle cx="585" cy="118" r="16" fill="#A5DB72"/><circle cx="618" cy="118" r="16" fill="#7CC04F"/></g>
            <path d="M640 120 C632 100 640 84 660 80 C664 98 656 114 640 120Z" fill="#8FCF5E"/>
            <path d="M180 56 C170 36 182 20 202 16 C206 36 198 52 180 56Z" fill="#8FCF5E" opacity=".9"/>
            <path d="M150 130 C140 112 150 98 168 96 C172 112 164 126 150 130Z" fill="#FFB6D5"/>
        </svg>
    </div>

    <a class="ct-add" href="{{ route('admin.accounting.export') }}">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14"/></svg>
        <span>Export CSV</span>
    </a>
</section>

{{-- Kartu statistik --}}
<div class="ac-stats">
    @foreach($stat as [$label, $val, $path, $tone, $money])
        <div class="ac-card">
            <span class="ac-ico {{ $tone }}"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $path }}"/></svg></span>
            <div class="ac-txt">
                <p class="ac-lbl">{{ $label }}</p>
                @if($money)
                    <p class="ac-val" data-idr="{{ $val }}">{{ $idr($val) }}</p>
                @else
                    <p class="ac-val">{{ $val }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Filter data --}}
<form method="GET" action="{{ route('admin.accounting.index') }}" id="fin-form" class="sh-panel ac-filter">
    <div class="ac-fh">
        <span class="ico"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg></span>
        <span>Filter data</span>
    </div>
    <div class="ac-row">
        <div><label class="sh-lbl" for="f-from">Date from</label><input id="f-from" class="sh-fld" type="date" name="date_from" value="{{ $filters['date_from'] }}"></div>
        <div><label class="sh-lbl" for="f-to">Date to</label><input id="f-to" class="sh-fld" type="date" name="date_to" value="{{ $filters['date_to'] }}"></div>
        <div>
            <label class="sh-lbl" for="f-status">Status</label>
            <select id="f-status" class="sh-sel" name="status">
                <option value="">All</option>
                <option value="PAID" @selected($filters['status'] === 'PAID')>Paid</option>
                <option value="PENDING" @selected($filters['status'] === 'PENDING')>Pending</option>
                <option value="FAILED" @selected($filters['status'] === 'FAILED')>Failed</option>
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-payout">Payout status</label>
            <select id="f-payout" class="sh-sel" name="payout">
                <option value="">All</option>
                <option value="PENDING_SETTLEMENT" @selected($filters['payout'] === 'PENDING_SETTLEMENT')>Pending settlement</option>
                <option value="SETTLED" @selected($filters['payout'] === 'SETTLED')>Settled</option>
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-method">Method</label>
            <select id="f-method" class="sh-sel" name="method">
                <option value="">All</option>
                @foreach($methods as $m)<option value="{{ $m }}" data-no-i18n @selected($filters['method'] === $m)>{{ str_replace('_', ' ', $m) }}</option>@endforeach
            </select>
        </div>
    </div>
    <input type="hidden" name="lang" value="" disabled>
    <div class="ac-btns">
        <button class="sh-btn sh-btn-apply" type="submit">
            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
            <span>Apply Filters</span>
        </button>
        <a class="sh-btn sh-btn-reset" href="{{ route('admin.accounting.index') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
            <span>Reset Filters</span>
        </a>
        <button class="sh-btn sh-btn-reset" type="submit" formaction="{{ route('admin.accounting.pdf') }}" data-pdf>
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 4v11m0 0-4-4m4 4 4-4M5 20h14"/></svg>
            <span>Download PDF report</span>
        </button>
    </div>
</form>

{{-- Daftar transaksi --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-table-head">
        <h2>
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $docIco }}"/></svg></span>
            <span>Transaction list</span>
        </h2>
        <div class="sh-per">
            <label for="f-per">Show</label>:
            <select id="f-per" class="sh-sel" onchange="var u=new URL(location.href);u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location.href=u.toString()">
                @foreach([10, 25, 50] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>@endforeach
            </select>
            @if($payments->onFirstPage())
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></span>
            @else
                <a class="sh-arrow" href="{{ $payments->previousPageUrl() }}" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></a>
            @endif
            @if($payments->hasMorePages())
                <a class="sh-arrow" href="{{ $payments->nextPageUrl() }}" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>
            @else
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
    </div>

    <div class="sh-tbl-wrap">
        <table class="sh-tbl ct-tbl ac-tbl">
            <thead>
                <tr>
                    <th style="width:4rem">No.</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th class="ct-c">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $i => $p)
                @php
                    [$stLabel, $stClass] = $statusMap[$p->status] ?? [ucfirst(strtolower($p->status)), 'sh-b-grey'];
                    $when = $p->paid_at ?? $p->created_at;
                    $settled_ = $p->payout_status === 'SETTLED';
                    $canMenu = $p->isPaid();
                @endphp
                <tr>
                    <td>{{ $payments->firstItem() + $i }}</td>
                    <td data-no-i18n>{{ $when?->format('d M Y, H:i') }}</td>
                    <td><span class="ac-desc"><span>Freight payment</span> <span data-no-i18n>#{{ $p->shipment?->shipment_number ?? '-' }}</span></span><span class="ac-sub" data-no-i18n>{{ $p->order_id }}</span></td>
                    <td data-no-i18n>{{ $p->shipment?->customer?->name ?? '-' }}</td>
                    <td data-idr="{{ $p->amount }}" style="white-space:nowrap">{{ $idr($p->amount) }}</td>
                    <td><span class="ac-meth"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $bank }}"/></svg><span data-no-i18n>{{ $p->payment_type ? str_replace('_', ' ', $p->payment_type) : '-' }}</span></span></td>
                    <td>
                        <span class="sh-badge {{ $stClass }}"><i></i><span>{{ $stLabel }}</span></span>
                        @if($p->isPaid())<span class="ac-pay"><span>{{ $settled_ ? 'Settled' : 'Pending settlement' }}</span></span>@endif
                    </td>
                    <td>
                        <div class="ac-act"
                             data-order="{{ $p->order_id }}" data-ship="{{ $p->shipment?->shipment_number }}" data-cust="{{ $p->shipment?->customer?->name }}"
                             data-method="{{ $p->payment_type ? str_replace('_', ' ', $p->payment_type) : '-' }}" data-amount="{{ $idr($p->amount) }}" data-raw="{{ $p->amount }}"
                             data-status="{{ $stLabel }}" data-paid="{{ $p->paid_at?->format('d M Y, H:i') ?? '-' }}"
                             data-payout="{{ $p->isPaid() ? ($settled_ ? 'Settled' : 'Pending settlement') : '-' }}"
                             data-settled="{{ $p->settled_at?->format('d M Y, H:i') ?? '-' }}">
                            <button type="button" class="ct-act ct-act-detail" data-act="detail">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Detail</span>
                            </button>
                            @if($canMenu)
                            <details class="sh-kebab">
                                <summary aria-label="Action"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg></summary>
                                <div class="sh-menu" hidden>
                                    <a class="ac-mi" data-pdf-lang href="{{ route('payments.receipt', $p) }}"><span>Receipt</span></a>
                                    @unless($settled_)
                                    <form method="POST" action="{{ route('admin.accounting.settle', $p) }}">@csrf
                                        <button type="submit" class="ac-mi"><span>Mark settled</span></button>
                                    </form>
                                    @endunless
                                </div>
                            </details>
                            @else
                                <span class="ac-none"></span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="sh-empty">No transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $payments])
    </div>
</div>

{{-- Pendapatan bulanan --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-table-head"><h2><span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 20h18M6 16V9m6 7V5m6 11v-4"/></svg></span><span>Monthly revenue</span></h2></div>
    <div class="ac-month">
        @forelse($monthly as $m => $v)
            <div><span data-no-i18n>{{ $m }}</span><b data-idr="{{ $v }}">{{ $idr($v) }}</b></div>
        @empty
            <div><span>No transactions yet.</span></div>
        @endforelse
    </div>
</div>

{{-- Dialog: detail transaksi --}}
<dialog id="dlg-detail" class="ct-dlg">
    <div class="ct-dlg-h">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span>
        <h3>Transaction detail</h3>
        <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="ct-dlg-b">
        <dl class="ct-dl">
            <dt>Order ID</dt><dd id="dt-order" data-no-i18n></dd>
            <dt>Shipment</dt><dd id="dt-ship" data-no-i18n></dd>
            <dt>Customer</dt><dd id="dt-cust" data-no-i18n></dd>
            <dt>Method</dt><dd id="dt-method" data-no-i18n></dd>
            <dt>Amount</dt><dd id="dt-amount" data-no-i18n></dd>
            <dt>Status</dt><dd id="dt-status"></dd>
            <dt>Paid at</dt><dd id="dt-paid" data-no-i18n></dd>
            <dt>Payout status</dt><dd id="dt-payout"></dd>
            <dt>Settled at</dt><dd id="dt-settled" data-no-i18n></dd>
        </dl>
    </div>
    <div class="ct-dlg-f"><button type="button" class="sh-btn sh-btn-apply" data-close>Close</button></div>
</dialog>
@endsection

@push('scripts')
@include('partials.sh-scripts')
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var T = function (s) { return (window.CT && CT.tx) ? CT.tx(s) : s; };

    // Bahasa UI dipilih di browser: beri tahu server bahasa PDF (hanya saat tombol PDF ditekan).
    var form = $('fin-form');
    form.addEventListener('submit', function (e) {
        var lang = form.elements.lang;
        if (e.submitter && e.submitter.hasAttribute('data-pdf')) {
            var l = null; try { l = localStorage.getItem('ct_lang'); } catch (x) { /* ignore */ }
            lang.value = (l === 'id' || l === 'en') ? l : '';
            lang.disabled = false;
        } else { lang.disabled = true; }
    });

    document.querySelectorAll('.ac-act [data-act="detail"]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = b.closest('.ac-act').dataset;
            $('dt-order').textContent = d.order; $('dt-ship').textContent = d.ship || '-'; $('dt-cust').textContent = d.cust || '-';
            $('dt-method').textContent = d.method; $('dt-amount').textContent = d.amount;
            $('dt-status').textContent = T(d.status); $('dt-paid').textContent = d.paid;
            $('dt-payout').textContent = d.payout === '-' ? '-' : T(d.payout); $('dt-settled').textContent = d.settled;
            $('dlg-detail').showModal();
        });
    });
    document.querySelectorAll('dialog.ct-dlg').forEach(function (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) { dlg.close(); } });
    });
})();
</script>
@endpush
