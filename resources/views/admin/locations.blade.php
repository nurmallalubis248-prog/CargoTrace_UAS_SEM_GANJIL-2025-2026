@extends('layouts.app')
@section('title', 'Locations')

@push('head')
@include('partials.sh-styles')
@include('partials.ct-admin-styles')
<style>
    /* ===== Khusus halaman Lokasi ===== */
    .lc-filter { display: grid; gap: 1rem 1.1rem; grid-template-columns: 1fr; }
    .lc-filter, .lc-filter * { box-sizing: border-box; }
    .lc-filter > * { min-width: 0; }
    @media (min-width: 768px)  { .lc-filter .lc-r4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .lc-filter .lc-row.lc-r4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } .lc-filter .lc-row.lc-r3 { grid-template-columns: 1fr 1fr 12rem; } }
    .lc-row { display: grid; gap: 1rem 1.1rem; grid-template-columns: 1fr; align-items: end; }
    @media (min-width: 768px) { .lc-row.lc-r3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .lc-pair { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid #F6D2E1; border-radius: 1rem; background: #fff; overflow: hidden; height: 2.9rem; }
    .lc-pair input { width: 100%; height: 100%; border: 0; background: transparent; padding: 0 .9rem; font-size: .88rem; color: #2B3027; min-width: 0; }
    .lc-pair input + input { border-left: 1px solid #F6D2E1; }
    .lc-pair input:focus { outline: none; background: #FFF7FA; }
    .lc-pair input:disabled { background: #FAF4F7; color: #9AA290; cursor: not-allowed; }
    .lc-tg { display: flex; align-items: center; gap: .7rem; height: 2.9rem; font-size: .88rem; font-weight: 700; cursor: pointer; }
    .lc-tg input { position: absolute; opacity: 0; pointer-events: none; }
    .lc-tg i { position: relative; width: 3.1rem; height: 1.7rem; border-radius: 9999px; background: #FFE3EE; box-shadow: inset 0 0 0 1px #F6D2E1; transition: background .2s; flex: none; }
    .lc-tg i::after { content: ''; position: absolute; top: .2rem; left: .2rem; width: 1.3rem; height: 1.3rem; border-radius: 9999px; background: #fff; box-shadow: 0 2px 6px rgba(233,30,140,.35); transition: transform .2s; }
    .lc-tg input:checked + i { background: linear-gradient(90deg, #FF6FAE, #F0508F); }
    .lc-tg input:checked + i::after { transform: translateX(1.4rem); }
    .lc-tg input:focus-visible + i { outline: 2px solid #F0508F; outline-offset: 2px; }
    .lc-btns { display: flex; flex-wrap: wrap; gap: .8rem; }

    .lc-tbl { min-width: 56rem; }
    .lc-type { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .85rem .35rem .65rem; border-radius: 9999px; font-size: .8rem; font-weight: 700; line-height: 1.1; }
    .lc-type svg { width: 1rem; height: 1rem; flex: none; }
    .lc-t-PORT { background: #DDF3C8; color: #2F6B12; }
    .lc-t-AIRPORT { background: #D6EBFF; color: #1D5FA8; }
    .lc-t-OTHER { background: #E9EAE6; color: #565E4E; }
    .lc-off { margin-left: .4rem; padding: .1rem .5rem; border-radius: 9999px; background: #E9EAE6; color: #565E4E; font-size: .68rem; font-weight: 700; vertical-align: middle; }
    .lc-act { display: flex; align-items: center; justify-content: center; gap: .4rem; }
    .lc-act .ct-act-detail { padding: .45rem 1rem; }
    .lc-mi { display: block; width: 100%; text-align: left; padding: .55rem .8rem; border: 0; border-radius: .7rem; background: transparent; font-size: .85rem; font-weight: 700; color: #2B3027; cursor: pointer; }
    .lc-mi:hover { background: #FFF0F6; color: #E91E8C; }
    .lc-mi-del { color: #C81E4F; } .lc-mi-del:hover { background: #FFE4EA; color: #C81E4F; }
    .lc-geo { display: flex; align-items: center; gap: .7rem; flex-wrap: wrap; }
    .lc-geo p { font-size: .75rem; color: #565E4E; flex: 1 1 12rem; }
    .lc-g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .lc-per { display: flex; align-items: center; gap: .6rem; }
</style>
@endpush

@section('content')
@php
    $total = $locations->total();
    $from = $total ? $locations->firstItem() : 0;
    $to = $total ? $locations->lastItem() : 0;
    $perPage = $filters['per_page'];
    $hasCoords = collect(['lat_min', 'lat_max', 'lng_min', 'lng_max'])->contains(fn ($k) => $filters[$k] !== null);
    $reopen = old('_mode');
    $region = fn (string $cc) => class_exists(\Locale::class) ? (\Locale::getDisplayRegion('-'.$cc, 'en') ?: $cc) : $cc;
    $typeLabel = ['PORT' => 'Port', 'AIRPORT' => 'Airport', 'OTHER' => 'Other'];
    $typeIco = [
        'PORT' => '<path d="M12 3v3m0 0a2 2 0 1 0 0 .01M12 8v13M5 14c0 3.9 3.1 7 7 7s7-3.1 7-7M5 14H3m18 0h-2M8 11h8"/>',
        'AIRPORT' => '<path d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5Z"/>',
        'OTHER' => '<path d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 0c0 7.5-9 11.25-9 11.25S3 18 3 10.5a9 9 0 1 1 18 0Z"/>',
    ];
    $pin = 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 0c0 7.5-9 11.25-9 11.25S3 18 3 10.5a9 9 0 1 1 18 0Z';
@endphp

{{-- Kepala halaman --}}
<section class="ct-hero">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $pin }}"/></svg></span>
        <div>
            <h1 data-i18n="locations">Locations</h1>
            <p>Manage shipping and distribution location data.</p>
        </div>
    </div>

    <div class="ct-hero-art" aria-hidden="true">
        <svg viewBox="0 0 700 200" preserveAspectRatio="xMaxYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="lgMap" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#E3F4CC"/><stop offset="1" stop-color="#C9EBA0"/></linearGradient>
                <linearGradient id="lgSea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFE9F2" stop-opacity="0"/><stop offset="1" stop-color="#FFD3E6" stop-opacity=".85"/></linearGradient>
                <g id="lgPin"><path d="M0 0C-17-19-17-43 0-43C17-43 17-19 0 0Z" fill="#FF5C9A"/><circle cy="-28" r="6.5" fill="#fff"/></g>
            </defs>
            <rect x="60" y="150" width="640" height="50" fill="url(#lgSea)"/>
            <path d="M40 140 C70 96 130 90 170 104 C206 70 270 70 300 96 C340 80 400 86 424 112 C470 96 530 100 560 124 C600 112 650 112 690 128 L690 156 L40 156Z" fill="url(#lgMap)" opacity=".95"/>
            <path d="M120 128 C150 112 190 116 214 130 M300 120 C330 108 372 112 396 128 M470 128 C500 116 540 118 566 134" stroke="#fff" stroke-width="2" fill="none" opacity=".8"/>
            <path d="M178 104 C230 34 300 70 368 70 C420 70 440 40 480 52" stroke="#FF5C9A" stroke-width="2.2" stroke-dasharray="6 7" fill="none" stroke-linecap="round"/>
            <use href="#lgPin" x="178" y="108"/><use href="#lgPin" x="368" y="82"/><use href="#lgPin" x="480" y="68"/>
            <g transform="translate(222 112)">
                <path d="M0 40H246L222 68H26Z" fill="#5E6B63"/><path d="M6 40H240" stroke="#FF8DBF" stroke-width="3"/>
                <g>
                    <rect x="64" y="22" width="30" height="16" fill="#FF8DBF"/><rect x="96" y="22" width="30" height="16" fill="#FFD3E6"/><rect x="128" y="22" width="30" height="16" fill="#FF5C9A"/><rect x="160" y="22" width="30" height="16" fill="#FFB6D5"/>
                    <rect x="80" y="6" width="30" height="16" fill="#FFD3E6"/><rect x="112" y="6" width="30" height="16" fill="#FF8DBF"/><rect x="144" y="6" width="30" height="16" fill="#FFB6D5"/>
                </g>
                <rect x="12" y="-2" width="34" height="40" rx="3" fill="#fff"/><rect x="18" y="4" width="22" height="6" fill="#CFEFFF"/><rect x="22" y="-14" width="8" height="12" fill="#FF5C9A"/>
            </g>
            <path d="M230 190 q20-8 40 0 t40 0 t40 0 t40 0 t40 0" stroke="#fff" stroke-width="2" fill="none" opacity=".7"/>
            <path d="M610 70 C590 44 598 20 622 10 C634 34 630 56 610 70Z" fill="#8FCF5E"/>
            <path d="M650 80 C640 58 650 40 672 36 C676 56 668 72 650 80Z" fill="#A5DB72"/>
            <path d="M560 30 C572 20 588 22 594 34 C582 44 568 42 560 30Z" fill="#FFB6D5"/>
        </svg>
    </div>

    @writable
    <button type="button" class="ct-add" data-open-add>
        <svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        <span>Add Location</span>
    </button>
    @endwritable
</section>

{{-- Kartu filter --}}
<form method="GET" action="{{ route('admin.locations.index') }}" class="sh-panel lc-filter">
    <div class="lc-row lc-r4" style="display:grid">
        <div>
            <label class="sh-lbl" for="f-name">Name</label>
            <input id="f-name" class="sh-fld" type="text" name="name" value="{{ $filters['name'] }}" placeholder="Search location name...">
        </div>
        <div>
            <label class="sh-lbl" for="f-code">Code</label>
            <input id="f-code" class="sh-fld" type="text" name="code" value="{{ $filters['code'] }}" maxlength="10" placeholder="Search location code...">
        </div>
        <div>
            <label class="sh-lbl" for="f-country">Country</label>
            <select id="f-country" class="sh-sel" name="country">
                <option value="">All</option>
                @foreach($countries as $cc)
                    <option value="{{ $cc }}" data-cc="{{ $cc }}" data-no-i18n @selected($filters['country'] === $cc)>{{ $region($cc) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-type">Type</label>
            <select id="f-type" class="sh-sel" name="type">
                <option value="">All</option>
                @foreach($typeLabel as $v => $l)<option value="{{ $v }}" @selected($filters['type'] === $v)>{{ $l }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="lc-row lc-r3" style="display:grid">
        <div>
            <label class="sh-lbl">Latitude</label>
            <div class="lc-pair"><input type="number" step="any" name="lat_min" value="{{ $filters['lat_min'] }}" placeholder="Min" min="-90" max="90" data-coord><input type="number" step="any" name="lat_max" value="{{ $filters['lat_max'] }}" placeholder="Max" min="-90" max="90" data-coord></div>
        </div>
        <div>
            <label class="sh-lbl">Longitude</label>
            <div class="lc-pair"><input type="number" step="any" name="lng_min" value="{{ $filters['lng_min'] }}" placeholder="Min" min="-180" max="180" data-coord><input type="number" step="any" name="lng_max" value="{{ $filters['lng_max'] }}" placeholder="Max" min="-180" max="180" data-coord></div>
        </div>
        <div>
            <label class="sh-lbl">Look up coordinates</label>
            <label class="lc-tg"><input type="checkbox" id="coord-toggle" @checked($hasCoords)><i></i><span data-no-i18n id="coord-state"></span></label>
        </div>
    </div>

    <div class="lc-btns">
        <button class="sh-btn sh-btn-apply" type="submit">
            <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
            <span>Apply Filters</span>
        </button>
        <a class="sh-btn sh-btn-reset" href="{{ route('admin.locations.index') }}" style="text-decoration:none">
            <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
            <span>Reset Filters</span>
        </a>
    </div>
</form>

{{-- Tabel data --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-table-head">
        <h2>
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $pin }}"/></svg></span>
            <span>Location list</span>
        </h2>
        <div class="sh-per">
            <label for="f-per">Show</label>:
            <select id="f-per" class="sh-sel" name="per_page" onchange="var u=new URL(location.href);u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location.href=u.toString()">
                @foreach([10, 25, 50] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>@endforeach
            </select>
            @if($locations->onFirstPage())
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></span>
            @else
                <a class="sh-arrow" href="{{ $locations->previousPageUrl() }}" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></a>
            @endif
            @if($locations->hasMorePages())
                <a class="sh-arrow" href="{{ $locations->nextPageUrl() }}" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>
            @else
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
    </div>

    <div class="sh-tbl-wrap">
        <table class="sh-tbl ct-tbl lc-tbl">
            <thead>
                <tr>
                    <th style="width:4.5rem">No.</th>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Country</th>
                    <th>Type</th>
                    <th>Lat / Lng</th>
                    <th class="ct-c">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($locations as $i => $l)
                @php $used = $l->origin_shipments_count + $l->destination_shipments_count; @endphp
                <tr>
                    <td>{{ $locations->firstItem() + $i }}</td>
                    <td class="ct-name">{{ $l->name }}@unless($l->is_active)<span class="lc-off">Inactive</span>@endunless</td>
                    <td>{{ $l->code }}</td>
                    <td><span data-cc="{{ $l->country }}" data-no-i18n>{{ $region($l->country) }}</span></td>
                    <td><span class="lc-type lc-t-{{ $l->type }}"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $typeIco[$l->type] ?? $typeIco['OTHER'] !!}</svg><span>{{ $typeLabel[$l->type] ?? $l->type }}</span></span></td>
                    <td class="sh-mono">{{ rtrim(rtrim(number_format($l->latitude, 4, '.', ''), '0'), '.') }}, {{ rtrim(rtrim(number_format($l->longitude, 4, '.', ''), '0'), '.') }}</td>
                    <td>
                        <div class="lc-act"
                             data-id="{{ $l->id }}" data-name="{{ $l->name }}" data-code="{{ $l->code }}" data-country="{{ $l->country }}"
                             data-type="{{ $l->type }}" data-lat="{{ $l->latitude }}" data-lng="{{ $l->longitude }}"
                             data-active="{{ $l->is_active ? 1 : 0 }}" data-used="{{ $used }}">
                            <button type="button" class="ct-act ct-act-detail" data-act="detail">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Detail</span>
                            </button>
                            @writable
                            <details class="sh-kebab">
                                <summary aria-label="Action"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg></summary>
                                <div class="sh-menu" hidden>
                                    <button type="button" class="lc-mi" data-act="edit"><span data-no-i18n>Edit</span></button>
                                    <form method="POST" action="{{ route('admin.locations.toggle', $l) }}">@csrf @method('PATCH')
                                        <button type="submit" class="lc-mi"><span>{{ $l->is_active ? 'Deactivate' : 'Activate' }}</span></button>
                                    </form>
                                    <button type="button" class="lc-mi lc-mi-del" data-act="delete"><span>Delete</span></button>
                                </div>
                            </details>
                            @endwritable
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="sh-empty">No locations found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $locations])
    </div>
</div>

{{-- Dialog: tambah / ubah --}}
<dialog id="dlg-form" class="ct-dlg">
    <form method="POST" action="{{ route('admin.locations.store') }}" id="loc-form" autocomplete="off">
        @csrf
        <input type="hidden" name="_method" value="POST" id="cf-method">
        <input type="hidden" name="_mode" value="add" id="cf-mode">
        <input type="hidden" name="_id" value="" id="cf-id">
        <div class="ct-dlg-h">
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $pin }}"/></svg></span>
            <h3 id="cf-title">Add Location</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <div><label class="sh-lbl" for="cf-name">Name *</label><input id="cf-name" class="sh-fld" name="name" required maxlength="255"></div>
            <div class="lc-g2">
                <div><label class="sh-lbl" for="cf-code">Code * (max 10)</label><input id="cf-code" class="sh-fld" name="code" required maxlength="10" style="text-transform:uppercase"></div>
                <div><label class="sh-lbl" for="cf-country">Country * (ISO-2)</label><input id="cf-country" class="sh-fld" name="country" required minlength="2" maxlength="2" style="text-transform:uppercase"></div>
            </div>
            <div><label class="sh-lbl" for="cf-type">Type *</label>
                <select id="cf-type" class="sh-sel" name="type">@foreach($typeLabel as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
            </div>
            <div class="lc-g2">
                <div><label class="sh-lbl" for="cf-lat">Latitude *</label><input id="cf-lat" class="sh-fld" type="number" step="any" name="latitude" required min="-90" max="90"></div>
                <div><label class="sh-lbl" for="cf-lng">Longitude *</label><input id="cf-lng" class="sh-fld" type="number" step="any" name="longitude" required min="-180" max="180"></div>
            </div>
            <div class="lc-geo">
                <button type="button" class="sh-btn sh-btn-reset" id="geocode-btn" style="height:2.5rem;padding:0 1.1rem;font-size:.82rem">Look up coordinates</button>
                <p id="geocode-msg" data-no-i18n>OpenStreetMap Nominatim (external API). If it fails, enter coordinates manually.</p>
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
        <h3>Location detail</h3>
        <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="ct-dlg-b">
        <dl class="ct-dl">
            <dt>Name</dt><dd id="dt-name"></dd>
            <dt>Code</dt><dd id="dt-code"></dd>
            <dt>Country</dt><dd id="dt-country" data-no-i18n></dd>
            <dt>Type</dt><dd id="dt-type"></dd>
            <dt>Lat / Lng</dt><dd id="dt-ll"></dd>
            <dt>Status</dt><dd id="dt-status"></dd>
            <dt>Shipments</dt><dd id="dt-used"></dd>
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
            <h3>Delete location</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <p class="ct-del-msg"><span>Are you sure you want to delete this location?</span> <b id="del-name"></b></p>
            <p class="ct-del-msg" id="del-warn" hidden style="color:#B45309"><span>This location is used by shipments and cannot be deleted. Deactivate it instead.</span></p>
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
    var lang = function () { try { return localStorage.getItem('ct_lang') || 'id'; } catch (e) { return 'id'; } };
    var storeUrl = @json(route('admin.locations.store'));
    var updateUrl = @json(route('admin.locations.update', ['location' => '__ID__']));
    var deleteUrl = @json(route('admin.locations.destroy', ['location' => '__ID__']));
    var geoUrl = @json(route('geocode'));
    var TYPES = { PORT: 'Port', AIRPORT: 'Airport', OTHER: 'Other' };

    // Nama negara mengikuti bahasa yang dipilih (Intl), cadangan: nama bahasa Inggris dari server
    function country(cc) { try { return new Intl.DisplayNames([lang()], { type: 'region' }).of(cc) || cc; } catch (e) { return null; } }
    function paintCountries() { document.querySelectorAll('[data-cc]').forEach(function (el) { var n = country(el.getAttribute('data-cc')); if (n) { el.textContent = n; } }); }
    paintCountries(); document.addEventListener('ct:lang', paintCountries);

    // Toggle "Cari koordinat": mengaktifkan kolom rentang Lintang/Bujur
    var tg = $('coord-toggle');
    function paintToggle() {
        document.querySelectorAll('[data-coord]').forEach(function (i) { i.disabled = !tg.checked; });
        $('coord-state').textContent = T(tg.checked ? 'Active' : 'Inactive');
    }
    tg.addEventListener('change', paintToggle); paintToggle(); document.addEventListener('ct:lang', paintToggle);

    var form = $('loc-form');
    function openForm(mode, d) {
        var edit = mode === 'edit';
        form.action = edit ? updateUrl.replace('__ID__', d.id) : storeUrl;
        $('cf-method').value = edit ? 'PUT' : 'POST'; $('cf-mode').value = mode; $('cf-id').value = edit ? d.id : '';
        $('cf-title').textContent = T(edit ? 'Edit location' : 'Add Location');
        $('cf-name').value = d.name || ''; $('cf-code').value = d.code || ''; $('cf-country').value = d.country || '';
        $('cf-type').value = d.type || 'PORT'; $('cf-lat').value = d.lat || ''; $('cf-lng').value = d.lng || '';
        $('cf-active').checked = d.active !== '0' && d.active !== 0 && d.active !== false;
        $('dlg-form').showModal(); $('cf-name').focus();
    }
    function rowData(el) { var d = el.closest('.lc-act').dataset; return { id: d.id, name: d.name, code: d.code, country: d.country, type: d.type, lat: d.lat, lng: d.lng, active: d.active, used: d.used }; }

    var addBtn = document.querySelector('[data-open-add]');   /* tidak ada di Mode Viewer */
    if (addBtn) { addBtn.addEventListener('click', function () { openForm('add', {}); }); }

    document.querySelectorAll('.lc-act [data-act]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = rowData(b), act = b.getAttribute('data-act');
            var det = b.closest('details'); if (det) { det.removeAttribute('open'); var m = det.querySelector('.sh-menu'); if (m) { m.hidden = true; } }
            if (act === 'edit') { openForm('edit', d); }
            else if (act === 'detail') {
                $('dt-name').textContent = d.name; $('dt-code').textContent = d.code; $('dt-country').textContent = country(d.country) || d.country;
                $('dt-type').textContent = T(TYPES[d.type] || d.type); $('dt-ll').textContent = d.lat + ', ' + d.lng;
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

    // Cari koordinat (OpenStreetMap Nominatim lewat server)
    $('geocode-btn').addEventListener('click', function () {
        var q = $('cf-name').value.trim(), msg = $('geocode-msg');
        if (q.length < 3) { msg.textContent = 'Type at least 3 characters in the name first.'; return; }
        msg.textContent = 'Searching…';
        fetch(geoUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
            .then(function (d) {
                if (!d.available) { msg.textContent = 'Geocoding unavailable: ' + d.message; return; }
                if (!d.results.length) { msg.textContent = d.message || 'No places found.'; return; }
                $('cf-lat').value = d.results[0].latitude; $('cf-lng').value = d.results[0].longitude;
                msg.textContent = 'Filled from: ' + d.results[0].name;
            })
            .catch(function () { msg.textContent = 'Look-up failed. Enter coordinates manually.'; });
    });

    @if($reopen)
    openForm(@json($reopen), { id: @json(old('_id')), name: @json(old('name')), code: @json(old('code')), country: @json(old('country')), type: @json(old('type', 'PORT')), lat: @json(old('latitude')), lng: @json(old('longitude')), active: @json((string) old('is_active', '1')) });
    @endif
})();
</script>
@endpush
