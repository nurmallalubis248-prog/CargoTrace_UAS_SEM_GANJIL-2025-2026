@extends('layouts.app')
@section('title', 'Carriers')

@push('head')
@include('partials.sh-styles')
@include('partials.ct-admin-styles')
@endpush

@section('content')
@php
    $total = $carriers->total();
    $from = $total ? $carriers->firstItem() : 0;
    $to = $total ? $carriers->lastItem() : 0;
    $modes = ['SEA' => 'Sea', 'AIR' => 'Air', 'ROAD' => 'Road'];
    $reopen = old('_mode');   // dialog dibuka lagi bila validasi gagal
    $ic = [
        'truck' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.38a1.13 1.13 0 0 1-1.13-1.13V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.13c.62 0 1.12-.5 1.12-1.13V14.25m-17.25 0V6.38c0-.62.5-1.13 1.13-1.13h9.74c.62 0 1.13.5 1.13 1.13v11.62m0-11.62h2.9c.3 0 .58.12.8.33l3.3 3.3c.2.2.33.5.33.8v3.62',
    ];
@endphp

{{-- Kepala halaman --}}
<section class="ct-hero">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic['truck'] }}"/></svg></span>
        <div>
            <h1 data-i18n="carriers">Carriers</h1>
            <p>Manage carrier data in the CargoTrace system.</p>
        </div>
    </div>

    <div class="ct-hero-art" aria-hidden="true">
        <svg viewBox="0 0 680 200" preserveAspectRatio="xMaxYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="hl1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#D9F2B8"/><stop offset="1" stop-color="#B9E67F"/></linearGradient>
                <linearGradient id="hl2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#C4EA8E"/><stop offset="1" stop-color="#A2DA6C"/></linearGradient>
                <linearGradient id="trk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FF8DBF"/><stop offset="1" stop-color="#EE4F8F"/></linearGradient>
                <linearGradient id="cab" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset="1" stop-color="#FFD3E6"/></linearGradient>
            </defs>
            <path d="M120 200 C170 120 260 96 330 126 C400 150 470 70 560 84 C620 94 650 70 680 60 V200Z" fill="url(#hl1)"/>
            <path d="M0 200 C90 150 200 150 300 168 C420 188 520 150 680 138 V200Z" fill="url(#hl2)"/>
            <path d="M150 188 C270 166 420 160 560 176 L680 186 V200 H150Z" fill="#A9A7B8"/>
            <path d="M190 192 C300 176 420 172 540 184" stroke="#fff" stroke-width="2" stroke-dasharray="14 12" fill="none" opacity=".9"/>
            {{-- Daun --}}
            <g>
                <path d="M520 78 C500 50 506 22 534 10 C546 36 540 62 520 78Z" fill="#8FCF5E"/><path d="M522 74 C524 54 530 34 534 14" stroke="#6FB445" stroke-width="1.4" fill="none"/>
                <path d="M566 72 C556 50 566 32 588 28 C592 48 584 64 566 72Z" fill="#A5DB72"/>
                <path d="M450 40 C462 30 478 32 484 44 C472 54 458 52 450 40Z" fill="#FFB6D5"/>
                <path d="M630 40 C640 26 656 26 664 38 C652 50 638 50 630 40Z" fill="#8FCF5E"/>
            </g>
            {{-- Truk --}}
            <g transform="translate(-112 0)">
                <ellipse cx="400" cy="178" rx="210" ry="8" fill="#000" opacity=".1"/>
                <rect x="250" y="52" width="320" height="82" rx="12" fill="url(#trk)"/>
                <rect x="250" y="52" width="320" height="14" rx="7" fill="#fff" opacity=".28"/>
                <rect x="268" y="84" width="284" height="5" rx="2.5" fill="#fff" opacity=".35"/>
                <path d="M254 134 V76 Q254 64 242 64 H208 Q192 64 184 82 L170 108 V134Z" fill="url(#cab)"/>
                <path d="M240 72 H210 Q199 72 193 86 L184 106 H240Z" fill="#CFEFFF"/>
                <path d="M240 72 H210 Q199 72 193 86 L190 92 L240 80Z" fill="#fff" opacity=".5"/>
                <rect x="170" y="108" width="12" height="9" rx="3" fill="#FFD84D"/>
                <rect x="166" y="124" width="92" height="12" rx="5" fill="#4E4B5E"/>
                <rect x="256" y="132" width="314" height="8" rx="4" fill="#5B5870"/>
                {{-- Roda --}}
                <g fill="#2A2833"><circle cx="212" cy="140" r="17"/><circle cx="454" cy="140" r="17"/><circle cx="500" cy="140" r="17"/><circle cx="546" cy="140" r="17"/></g>
                <g fill="#D7D5E3"><circle cx="212" cy="140" r="7"/><circle cx="454" cy="140" r="7"/><circle cx="500" cy="140" r="7"/><circle cx="546" cy="140" r="7"/></g>
            </g>
        </svg>
    </div>

    <button type="button" class="ct-add" data-open-add>
        <svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        <span>Add Carrier</span>
    </button>
</section>

{{-- Kartu filter --}}
<form method="GET" action="{{ route('admin.carriers.index') }}" class="sh-panel ct-filter">
    <div>
        <label class="sh-lbl" for="f-name">Carrier name</label>
        <input id="f-name" class="sh-fld" type="text" name="name" value="{{ $filters['name'] }}" placeholder="Search carrier name...">
    </div>
    <div>
        <label class="sh-lbl" for="f-code">Code</label>
        <input id="f-code" class="sh-fld" type="text" name="code" value="{{ $filters['code'] }}" placeholder="Search code..." maxlength="10">
    </div>
    <div>
        <label class="sh-lbl" for="f-status">Status</label>
        <select id="f-status" class="sh-sel" name="status">
            <option value="">All</option>
            <option value="active" @selected($filters['status'] === 'active')>Active</option>
            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
        </select>
    </div>
    <button class="sh-btn sh-btn-apply" type="submit">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span>Apply Filters</span>
    </button>
    <a class="sh-btn sh-btn-reset" href="{{ route('admin.carriers.index') }}">
        <svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5"/></svg>
        <span>Reset Filters</span>
    </a>
</form>

{{-- Tabel data --}}
<div class="sh-panel" style="margin-top:1.1rem">
    <div class="sh-tbl-wrap">
        <table class="sh-tbl ct-tbl">
            <thead>
                <tr>
                    <th style="width:4.5rem">No.</th>
                    <th>Carrier name</th>
                    <th>Code</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th class="ct-c">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($carriers as $i => $c)
                <tr>
                    <td>{{ $carriers->firstItem() + $i }}</td>
                    <td class="ct-name">{{ $c->name }}</td>
                    <td>{{ $c->code }}</td>
                    <td>{{ $c->contact_email ?: '-' }}</td>
                    <td>
                        @if($c->is_active)
                            <span class="sh-badge sh-b-green"><i></i><span>Active</span></span>
                        @else
                            <span class="sh-badge sh-b-grey"><i></i><span>Inactive</span></span>
                        @endif
                    </td>
                    <td>
                        <div class="ct-acts"
                             data-id="{{ $c->id }}" data-name="{{ $c->name }}" data-code="{{ $c->code }}"
                             data-mode="{{ $c->transport_mode ?: 'SEA' }}" data-email="{{ $c->contact_email }}"
                             data-active="{{ $c->is_active ? 1 : 0 }}" data-shipments="{{ $c->shipments_count }}">
                            <button type="button" class="ct-act ct-act-detail" data-act="detail">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Detail</span>
                            </button>
                            <button type="button" class="ct-act ct-act-edit" data-act="edit">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                <span data-no-i18n>Edit</span>
                            </button>
                            <button type="button" class="ct-act ct-act-del" data-act="delete">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg>
                                <span>Delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="sh-empty">No carriers found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $carriers])
    </div>
</div>

{{-- Dialog: tambah / ubah --}}
<dialog id="dlg-form" class="ct-dlg">
    <form method="POST" action="{{ route('admin.carriers.store') }}" id="carrier-form" autocomplete="off">
        @csrf
        <input type="hidden" name="_method" value="POST" id="cf-method">
        <input type="hidden" name="_mode" value="add" id="cf-mode">
        <input type="hidden" name="_id" value="" id="cf-id">
        <div class="ct-dlg-h">
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic['truck'] }}"/></svg></span>
            <h3 id="cf-title">Add Carrier</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <div><label class="sh-lbl" for="cf-name">Carrier name *</label><input id="cf-name" class="sh-fld" name="name" required maxlength="255"></div>
            <div><label class="sh-lbl" for="cf-code">Code * (max 10)</label><input id="cf-code" class="sh-fld" name="code" required maxlength="10" style="text-transform:uppercase"></div>
            <div><label class="sh-lbl" for="cf-mode-sel">Transport mode</label>
                <select id="cf-mode-sel" class="sh-sel" name="transport_mode">
                    @foreach($modes as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </select>
            </div>
            <div><label class="sh-lbl" for="cf-email">Contact email</label><input id="cf-email" class="sh-fld" type="email" name="contact_email" maxlength="255"></div>
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
        <h3>Carrier detail</h3>
        <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="ct-dlg-b">
        <dl class="ct-dl">
            <dt>Carrier name</dt><dd id="dt-name"></dd>
            <dt>Code</dt><dd id="dt-code"></dd>
            <dt>Transport mode</dt><dd id="dt-mode"></dd>
            <dt>Email</dt><dd id="dt-email"></dd>
            <dt>Status</dt><dd id="dt-status"></dd>
            <dt>Shipments</dt><dd id="dt-ship"></dd>
        </dl>
    </div>
    <div class="ct-dlg-f">
        <a id="dt-link" class="sh-btn sh-btn-reset" href="#">View shipments</a>
        <button type="button" class="sh-btn sh-btn-apply" data-close>Close</button>
    </div>
</dialog>

{{-- Dialog: konfirmasi hapus --}}
<dialog id="dlg-del" class="ct-dlg">
    <form method="POST" action="#" id="del-form">
        @csrf @method('DELETE')
        <div class="ct-dlg-h">
            <span class="ico" style="background:linear-gradient(135deg,#F25C85,#D9254F)"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg></span>
            <h3>Delete carrier</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <p class="ct-del-msg"><span>Are you sure you want to delete this carrier?</span> <b id="del-name"></b></p>
            <p class="ct-del-msg" id="del-warn" hidden style="color:#B45309"><span>This carrier has shipments and cannot be deleted. Deactivate it instead.</span></p>
        </div>
        <div class="ct-dlg-f">
            <button type="button" class="sh-btn sh-btn-reset" data-close>Cancel</button>
            <button type="submit" class="sh-btn sh-btn-danger" id="del-ok">Delete</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var T = function (s) { return (window.CT && CT.tx) ? CT.tx(s) : s; };
    var storeUrl = @json(route('admin.carriers.store'));
    var updateUrl = @json(route('admin.carriers.update', ['carrier' => '__ID__']));
    var deleteUrl = @json(route('admin.carriers.destroy', ['carrier' => '__ID__']));
    var shipUrl = @json(route('shipments.index'));
    var MODES = { SEA: 'Sea', AIR: 'Air', ROAD: 'Road' };

    var form = $('carrier-form');
    function openForm(mode, d) {
        var edit = mode === 'edit';
        form.action = edit ? updateUrl.replace('__ID__', d.id) : storeUrl;
        $('cf-method').value = edit ? 'PUT' : 'POST';
        $('cf-mode').value = mode; $('cf-id').value = edit ? d.id : '';
        $('cf-title').textContent = T(edit ? 'Edit carrier' : 'Add Carrier');
        $('cf-name').value = d.name || ''; $('cf-code').value = d.code || '';
        $('cf-mode-sel').value = d.mode || 'SEA'; $('cf-email').value = d.email || '';
        $('cf-active').checked = d.active !== '0' && d.active !== 0 && d.active !== false;
        $('dlg-form').showModal(); $('cf-name').focus();
    }
    function rowData(el) { var d = el.closest('.ct-acts').dataset; return { id: d.id, name: d.name, code: d.code, mode: d.mode, email: d.email, active: d.active, shipments: d.shipments }; }

    document.querySelector('[data-open-add]').addEventListener('click', function () { openForm('add', {}); });

    document.querySelectorAll('.ct-act').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = rowData(b), act = b.getAttribute('data-act');
            if (act === 'edit') { openForm('edit', d); }
            else if (act === 'detail') {
                $('dt-name').textContent = d.name; $('dt-code').textContent = d.code;
                $('dt-mode').textContent = T(MODES[d.mode] || d.mode); $('dt-email').textContent = d.email || '-';
                $('dt-status').textContent = T(d.active === '1' ? 'Active' : 'Inactive'); $('dt-ship').textContent = d.shipments;
                $('dt-link').href = shipUrl + '?carrier_id=' + encodeURIComponent(d.id);
                $('dlg-detail').showModal();
            } else if (act === 'delete') {
                $('del-form').action = deleteUrl.replace('__ID__', d.id);
                $('del-name').textContent = d.name + ' (' + d.code + ')';
                var used = parseInt(d.shipments, 10) > 0;
                $('del-warn').hidden = !used; $('del-ok').disabled = used; $('del-ok').style.opacity = used ? .5 : 1;
                $('dlg-del').showModal();
            }
        });
    });

    document.querySelectorAll('dialog.ct-dlg').forEach(function (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) { dlg.close(); } });
    });

    // Buka kembali dialog tambah/ubah bila validasi server gagal (nilai lama dipertahankan)
    @if($reopen)
    openForm(@json($reopen), { id: @json(old('_id')), name: @json(old('name')), code: @json(old('code')), mode: @json(old('transport_mode', 'SEA')), email: @json(old('contact_email')), active: @json((string) old('is_active', '1')) });
    @endif
})();
</script>
@endpush
