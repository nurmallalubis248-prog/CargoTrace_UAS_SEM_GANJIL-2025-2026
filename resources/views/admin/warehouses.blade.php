@extends('layouts.app')
@section('title', 'Warehouses')

@push('head')
@include('partials.sh-styles')
@include('partials.ct-admin-styles')
<style>
    /* ===== Khusus halaman Gudang ===== */
    /* ilustrasi gudang di atas tombol Tambah Gudang (layar lebar) */
    @media (min-width: 1280px) {
        .ct-hero { min-height: 14.5rem; }
        .ct-hero-art { align-self: start; margin-top: .4rem; margin-right: 1.5rem; max-width: 27rem; height: 10rem; }
        .ct-add { bottom: .7rem; }
    }
    .wh-filter, .wh-filter * { box-sizing: border-box; }
    .wh-filter { display: grid; gap: 1rem 1.1rem; }
    .wh-row { display: grid; gap: 1rem 1.1rem; grid-template-columns: 1fr; align-items: end; }
    .wh-row > * { min-width: 0; }
    @media (min-width: 768px)  { .wh-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .wh-row.wh-r4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } .wh-row.wh-r3 { grid-template-columns: 1fr 1fr auto; } }
    .wh-pair { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #F6D2E1; border-radius: 1rem; background: #fff; overflow: hidden; height: 2.9rem; }
    .wh-pair input { width: 100%; height: 100%; border: 0; background: transparent; padding: 0 .9rem; font-size: .88rem; color: #2B3027; min-width: 0; }
    .wh-pair input + input { border-left: 1px solid #F6D2E1; }
    .wh-pair input:focus { outline: none; background: #FFF7FA; }
    .wh-btns { display: flex; flex-wrap: wrap; gap: .8rem; }
    .wh-btns .sh-btn { height: 2.9rem; }
    .wh-g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    .wh-tbl { min-width: 60rem; }
    .wh-code { font-weight: 700; letter-spacing: .02em; }
    .wh-loc { line-height: 1.25; font-size: .85rem; }
    .wh-util { display: flex; align-items: center; gap: .65rem; }
    .wh-bar { position: relative; flex: 0 0 5.5rem; height: .55rem; border-radius: 9999px; background: #EAEDE4; overflow: hidden; }
    .wh-bar i { position: absolute; inset: 0 auto 0 0; border-radius: 9999px; background: linear-gradient(90deg, #7CC04F, #4FA52F); }
    .wh-bar i.hi { background: linear-gradient(90deg, #FF8DBF, #F0508F); }
    .wh-pct { font-size: .8rem; font-weight: 700; color: #2B3027; font-variant-numeric: tabular-nums; }
    .wh-off { margin-left: .4rem; padding: .1rem .5rem; border-radius: 9999px; background: #E9EAE6; color: #565E4E; font-size: .68rem; font-weight: 700; vertical-align: middle; }
    .wh-act { display: flex; align-items: center; justify-content: center; gap: .4rem; }
    .wh-act .ct-act-detail { padding: .45rem 1rem; }
    .wh-mi { display: block; width: 100%; text-align: left; padding: .55rem .8rem; border: 0; border-radius: .7rem; background: transparent; font-size: .85rem; font-weight: 700; color: #2B3027; cursor: pointer; }
    .wh-mi:hover { background: #FFF0F6; color: #E91E8C; }
    .wh-mi-del { color: #C81E4F; } .wh-mi-del:hover { background: #FFE4EA; color: #C81E4F; }
</style>
@endpush

@section('content')
@php
    $total = $warehouses->total();
    $from = $total ? $warehouses->firstItem() : 0;
    $to = $total ? $warehouses->lastItem() : 0;
    $perPage = $filters['per_page'];
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $home = 'M3 21V9.5L12 4l9 5.5V21M7 21v-7h10v7M7 17.5h10M10 14v7M14 14v7';
    $reopen = old('_mode');
@endphp

{{-- Kepala halaman --}}
<section class="ct-hero">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $home }}"/></svg></span>
        <div>
            <h1>Warehouses</h1>
            <p>Manage warehouse data and storage capacity information.</p>
        </div>
    </div>

    <div class="ct-hero-art" aria-hidden="true">
        <svg viewBox="0 0 700 200" preserveAspectRatio="xMaxYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="whHill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#CDEBA0"/><stop offset="1" stop-color="#A9DC74"/></linearGradient>
                <linearGradient id="whRoof" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FF7DB5"/><stop offset="1" stop-color="#F0508F"/></linearGradient>
            </defs>
            <path d="M120 200 C200 150 300 150 380 168 C470 140 580 140 700 160 V200Z" fill="url(#whHill)"/>
            <path d="M260 200 C340 176 440 178 520 192 V200Z" fill="#B9E67F" opacity=".8"/>
            {{-- pin lokasi --}}
            <g transform="translate(330 98)"><path d="M0 0C-16-18-16-40 0-40C16-40 16-18 0 0Z" fill="#FF5C9A"/><circle cy="-26" r="6" fill="#fff"/><ellipse cy="4" rx="10" ry="3" fill="#FFB6D5" opacity=".8"/></g>
            {{-- gudang utama --}}
            <g transform="translate(396 78)">
                <rect x="0" y="40" width="170" height="82" fill="#FFF4F9"/>
                <path d="M-8 44 L85 6 L178 44Z" fill="url(#whRoof)"/>
                <rect x="52" y="70" width="66" height="52" rx="3" fill="#8A97A0"/>
                <path d="M56 80H114M56 90H114M56 100H114M56 110H114" stroke="#B9C4CB" stroke-width="2"/>
                <rect x="14" y="62" width="22" height="18" rx="2" fill="#CFEFFF"/><rect x="134" y="62" width="22" height="18" rx="2" fill="#CFEFFF"/>
                <rect x="14" y="92" width="22" height="18" rx="2" fill="#FFD3E6"/><rect x="134" y="92" width="22" height="18" rx="2" fill="#FFD3E6"/>
            </g>
            {{-- gudang kecil --}}
            <g transform="translate(570 100)">
                <rect x="0" y="30" width="80" height="60" fill="#FFF4F9"/><path d="M-6 34 L40 8 L86 34Z" fill="#FF8DBF"/>
                <rect x="26" y="54" width="28" height="36" rx="2" fill="#9AA6AE"/><rect x="8" y="44" width="12" height="10" fill="#CFEFFF"/><rect x="60" y="44" width="12" height="10" fill="#CFEFFF"/>
            </g>
            {{-- pohon --}}
            <g><rect x="190" y="138" width="6" height="30" fill="#A87858"/><circle cx="193" cy="124" r="22" fill="#8FCF5E"/><circle cx="180" cy="136" r="14" fill="#A5DB72"/><circle cx="206" cy="136" r="14" fill="#7CC04F"/></g>
            <g><rect x="252" y="150" width="5" height="22" fill="#A87858"/><circle cx="254" cy="140" r="16" fill="#A5DB72"/><circle cx="244" cy="150" r="10" fill="#8FCF5E"/></g>
            <g><rect x="668" y="120" width="6" height="40" fill="#A87858"/><circle cx="671" cy="104" r="26" fill="#8FCF5E"/><circle cx="655" cy="120" r="16" fill="#A5DB72"/><circle cx="688" cy="120" r="16" fill="#7CC04F"/></g>
            <path d="M610 40 C590 14 598 -6 622 -16 C634 8 630 30 610 40Z" fill="#8FCF5E" opacity=".9"/>
            <path d="M150 70 C140 52 150 36 170 32 C174 50 166 64 150 70Z" fill="#FFB6D5"/>
        </svg>
    </div>

    <button type="button" class="ct-add" data-open-add>
        <svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        <span>Add Warehouse</span>
    </button>
</section>

{{-- Kartu filter --}}
<form method="GET" action="{{ route('admin.warehouses.index') }}" class="sh-panel wh-filter">
    <div class="wh-row wh-r4">
        <div>
            <label class="sh-lbl" for="f-name">Name</label>
            <input id="f-name" class="sh-fld" type="text" name="name" value="{{ $filters['name'] }}" placeholder="Search warehouse name...">
        </div>
        <div>
            <label class="sh-lbl" for="f-code">Code</label>
            <input id="f-code" class="sh-fld" type="text" name="code" value="{{ $filters['code'] }}" maxlength="12" placeholder="Search warehouse code...">
        </div>
        <div>
            <label class="sh-lbl" for="f-loc">Linked location</label>
            <select id="f-loc" class="sh-sel" name="location_id">
                <option value="">All</option>
                @foreach($locations as $l)<option value="{{ $l->id }}" data-no-i18n @selected($filters['location_id'] === $l->id)>{{ $l->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-lcode">Location code</label>
            <input id="f-lcode" class="sh-fld" type="text" name="location_code" value="{{ $filters['location_code'] }}" maxlength="10" placeholder="Search location code...">
        </div>
    </div>

    <div class="wh-row wh-r3">
        <div>
            <label class="sh-lbl">Capacity</label>
            <div class="wh-pair"><input type="number" min="0" name="cap_min" value="{{ $filters['cap_min'] }}" placeholder="Min"><input type="number" min="0" name="cap_max" value="{{ $filters['cap_max'] }}" placeholder="Max"></div>
        </div>
        <div>
            <label class="sh-lbl">Load</label>
            <div class="wh-pair"><input type="number" min="0" name="load_min" value="{{ $filters['load_min'] }}" placeholder="Min"><input type="number" min="0" name="load_max" value="{{ $filters['load_max'] }}" placeholder="Max"></div>
        </div>
        <div class="wh-btns">
            <button class="sh-btn sh-btn-apply" type="submit">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
                <span>Apply Filters</span>
            </button>
            <a class="sh-btn sh-btn-reset" href="{{ route('admin.warehouses.index') }}" style="text-decoration:none">
                <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
                <span>Reset Filters</span>
            </a>
        </div>
    </div>
</form>

{{-- Tabel data --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-table-head">
        <h2>
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $home }}"/></svg></span>
            <span>Warehouse list</span>
        </h2>
        <div class="sh-per">
            <label for="f-per">Show</label>:
            <select id="f-per" class="sh-sel" name="per_page" onchange="var u=new URL(location.href);u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location.href=u.toString()">
                @foreach([10, 25, 50] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>@endforeach
            </select>
            @if($warehouses->onFirstPage())
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></span>
            @else
                <a class="sh-arrow" href="{{ $warehouses->previousPageUrl() }}" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></a>
            @endif
            @if($warehouses->hasMorePages())
                <a class="sh-arrow" href="{{ $warehouses->nextPageUrl() }}" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>
            @else
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
    </div>

    <div class="sh-tbl-wrap">
        <table class="sh-tbl ct-tbl wh-tbl">
            <thead>
                <tr>
                    <th style="width:4.5rem">No.</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Load</th>
                    <th>Utilization</th>
                    <th class="ct-c">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($warehouses as $i => $w)
                @php
                    $load = (float) $w->load_kg;
                    $raw = $w->capacity_kg > 0 ? $load / $w->capacity_kg * 100 : 0;
                    $pct = min(100, $raw);
                @endphp
                <tr>
                    <td>{{ $warehouses->firstItem() + $i }}</td>
                    <td class="ct-name">{{ $w->name }}@unless($w->is_active)<span class="wh-off">Inactive</span>@endunless</td>
                    <td class="wh-code">{{ $w->code }}</td>
                    <td class="wh-loc" data-no-i18n>{{ $w->location?->name ?? '-' }}</td>
                    <td>{{ $fmt($w->capacity_kg) }} kg</td>
                    <td>{{ $fmt($load) }} kg</td>
                    <td>
                        <div class="wh-util">
                            <span class="wh-bar"><i class="{{ $raw >= 85 ? 'hi' : '' }}" style="width: {{ number_format($pct, 1, '.', '') }}%"></i></span>
                            <span class="wh-pct">{{ number_format($pct, 1, '.', '') }}%</span>
                        </div>
                    </td>
                    <td>
                        <div class="wh-act"
                             data-id="{{ $w->id }}" data-name="{{ $w->name }}" data-code="{{ $w->code }}"
                             data-loc-id="{{ $w->location_id }}" data-loc="{{ $w->location?->name }}" data-loc-code="{{ $w->location?->code }}"
                             data-cap="{{ $w->capacity_kg }}" data-load="{{ $load }}" data-pct="{{ number_format($pct, 1, '.', '') }}"
                             data-active="{{ $w->is_active ? 1 : 0 }}" data-used="{{ $w->shipments_count }}">
                            <button type="button" class="ct-act ct-act-detail" data-act="detail">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Detail</span>
                            </button>
                            <details class="sh-kebab">
                                <summary aria-label="Action"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg></summary>
                                <div class="sh-menu" hidden>
                                    <button type="button" class="wh-mi" data-act="edit"><span>Edit</span></button>
                                    <form method="POST" action="{{ route('admin.warehouses.toggle', $w) }}">@csrf @method('PATCH')
                                        <button type="submit" class="wh-mi"><span>{{ $w->is_active ? 'Deactivate' : 'Activate' }}</span></button>
                                    </form>
                                    <button type="button" class="wh-mi wh-mi-del" data-act="delete"><span>Delete</span></button>
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="sh-empty">No warehouses found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $warehouses])
    </div>
</div>

{{-- Dialog: tambah / ubah --}}
<dialog id="dlg-form" class="ct-dlg">
    <form method="POST" action="{{ route('admin.warehouses.store') }}" id="wh-form" autocomplete="off">
        @csrf
        <input type="hidden" name="_method" value="POST" id="cf-method">
        <input type="hidden" name="_mode" value="add" id="cf-mode">
        <input type="hidden" name="_id" value="" id="cf-id">
        <div class="ct-dlg-h">
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $home }}"/></svg></span>
            <h3 id="cf-title">Add Warehouse</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <div><label class="sh-lbl" for="cf-name">Name *</label><input id="cf-name" class="sh-fld" name="name" required maxlength="120"></div>
            <div class="wh-g2">
                <div><label class="sh-lbl" for="cf-code">Code *</label><input id="cf-code" class="sh-fld" name="code" required maxlength="12" style="text-transform:uppercase"></div>
                <div><label class="sh-lbl" for="cf-cap">Capacity (kg) *</label><input id="cf-cap" class="sh-fld" type="number" min="1" max="100000000" name="capacity_kg" required></div>
            </div>
            <div><label class="sh-lbl" for="cf-loc">Linked location</label>
                <select id="cf-loc" class="sh-sel" name="location_id"><option value="">None</option>@foreach($locations as $l)<option value="{{ $l->id }}" data-no-i18n>{{ $l->name }}</option>@endforeach</select>
            </div>
            <label class="ct-sw"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" id="cf-active" checked> <span>Active</span></label>
        </div>
        <div class="ct-dlg-f">
            <button type="button" class="sh-btn sh-btn-reset" data-close>Cancel</button>
            <button type="submit" class="sh-btn sh-btn-apply">Save</button>
        </div>
    </form>
</dialog>

{{-- Dialog: detail --}}
<dialog id="dlg-detail" class="ct-dlg">
    <div class="ct-dlg-h">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></span>
        <h3>Warehouse detail</h3>
        <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="ct-dlg-b">
        <dl class="ct-dl">
            <dt>Name</dt><dd id="dt-name" data-no-i18n></dd>
            <dt>Code</dt><dd id="dt-code" data-no-i18n></dd>
            <dt>Location</dt><dd id="dt-loc" data-no-i18n></dd>
            <dt>Capacity</dt><dd id="dt-cap" data-no-i18n></dd>
            <dt>Load</dt><dd id="dt-load" data-no-i18n></dd>
            <dt>Free capacity</dt><dd id="dt-free" data-no-i18n></dd>
            <dt>Utilization</dt><dd id="dt-pct" data-no-i18n></dd>
            <dt>Status</dt><dd id="dt-status"></dd>
            <dt>Shipments</dt><dd id="dt-used" data-no-i18n></dd>
        </dl>
    </div>
    <div class="ct-dlg-f"><button type="button" class="sh-btn sh-btn-apply" data-close>Close</button></div>
</dialog>

{{-- Dialog: konfirmasi hapus --}}
<dialog id="dlg-del" class="ct-dlg">
    <form method="POST" action="#" id="del-form">
        @csrf @method('DELETE')
        <div class="ct-dlg-h">
            <span class="ico" style="background:linear-gradient(135deg,#F25C85,#D9254F)"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg></span>
            <h3>Delete warehouse</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <p class="ct-del-msg"><span>Are you sure you want to delete this warehouse?</span> <b id="del-name" data-no-i18n></b></p>
            <p class="ct-del-msg" id="del-warn" hidden style="color:#B45309"><span>This warehouse is used by shipments and cannot be deleted. Deactivate it instead.</span></p>
        </div>
        <div class="ct-dlg-f">
            <button type="button" class="sh-btn sh-btn-reset" data-close>Cancel</button>
            <button type="submit" class="sh-btn sh-btn-danger" id="del-ok">Delete</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
@include('partials.sh-scripts')
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var T = function (s) { return (window.CT && CT.tx) ? CT.tx(s) : s; };
    var nf = function (n) { return Number(n).toLocaleString('id-ID', { maximumFractionDigits: 0 }); };
    var storeUrl = @json(route('admin.warehouses.store'));
    var updateUrl = @json(route('admin.warehouses.update', ['warehouse' => '__ID__']));
    var deleteUrl = @json(route('admin.warehouses.destroy', ['warehouse' => '__ID__']));

    var form = $('wh-form');
    function openForm(mode, d) {
        var edit = mode === 'edit';
        form.action = edit ? updateUrl.replace('__ID__', d.id) : storeUrl;
        $('cf-method').value = edit ? 'PUT' : 'POST'; $('cf-mode').value = mode; $('cf-id').value = edit ? d.id : '';
        $('cf-title').textContent = T(edit ? 'Edit warehouse' : 'Add Warehouse');
        $('cf-name').value = d.name || ''; $('cf-code').value = d.code || ''; $('cf-cap').value = d.cap || '';
        $('cf-loc').value = d.locId || '';
        $('cf-active').checked = d.active !== '0' && d.active !== 0 && d.active !== false;
        $('dlg-form').showModal(); $('cf-name').focus();
    }
    function rowData(el) { var d = el.closest('.wh-act').dataset; return { id: d.id, name: d.name, code: d.code, locId: d.locId, loc: d.loc, locCode: d.locCode, cap: d.cap, load: d.load, pct: d.pct, active: d.active, used: d.used }; }

    document.querySelector('[data-open-add]').addEventListener('click', function () { openForm('add', {}); });

    document.querySelectorAll('.wh-act [data-act]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = rowData(b), act = b.getAttribute('data-act');
            var det = b.closest('details'); if (det) { det.removeAttribute('open'); var m = det.querySelector('.sh-menu'); if (m) { m.hidden = true; } }
            if (act === 'edit') { openForm('edit', d); }
            else if (act === 'detail') {
                var free = Math.max(0, d.cap - d.load);
                $('dt-name').textContent = d.name; $('dt-code').textContent = d.code;
                $('dt-loc').textContent = d.loc ? d.loc + ' (' + d.locCode + ')' : '-';
                $('dt-cap').textContent = nf(d.cap) + ' kg'; $('dt-load').textContent = nf(d.load) + ' kg';
                $('dt-free').textContent = nf(free) + ' kg'; $('dt-pct').textContent = d.pct + '%';
                $('dt-status').textContent = T(d.active === '1' ? 'Active' : 'Inactive'); $('dt-used').textContent = d.used;
                $('dlg-detail').showModal();
            } else if (act === 'delete') {
                $('del-form').action = deleteUrl.replace('__ID__', d.id);
                $('del-name').textContent = d.name + ' (' + d.code + ')';
                var used = parseInt(d.used, 10) > 0;
                $('del-warn').hidden = !used; $('del-ok').disabled = used; $('del-ok').style.opacity = used ? .5 : 1;
                $('dlg-del').showModal();
            }
        });
    });

    document.querySelectorAll('dialog.ct-dlg').forEach(function (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) { dlg.close(); } });
    });

    @if($reopen)
    openForm(@json($reopen), { id: @json(old('_id')), name: @json(old('name')), code: @json(old('code')), cap: @json(old('capacity_kg')), locId: @json(old('location_id')), active: @json((string) old('is_active', '1')) });
    @endif
})();
</script>
@endpush
