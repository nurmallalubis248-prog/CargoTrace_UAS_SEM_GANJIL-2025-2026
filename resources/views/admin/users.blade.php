@extends('layouts.app')
@section('title', 'Users')

@push('head')
@include('partials.sh-styles')
@include('partials.ct-admin-styles')
<style>
    /* ===== Khusus halaman Pengguna ===== */
    @media (min-width: 1280px) {
        .ct-hero { min-height: 14.5rem; }
        .ct-hero-art { align-self: start; margin-top: .4rem; margin-right: 1.5rem; max-width: 27rem; height: 10rem; }
        .ct-add { bottom: .7rem; }
    }
    .us-filter, .us-filter * { box-sizing: border-box; }
    .us-row { display: grid; gap: 1rem 1.1rem; grid-template-columns: 1fr; align-items: end; }
    .us-row > * { min-width: 0; }
    @media (min-width: 768px)  { .us-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1100px) { .us-row { grid-template-columns: 1.2fr 1.2fr 1fr 1fr auto; } }
    .us-btns { display: flex; flex-wrap: wrap; gap: .8rem; }
    .us-btns .sh-btn { height: 2.9rem; text-decoration: none; }
    .us-g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    .us-tbl { min-width: 58rem; }
    .us-role { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .85rem .35rem .65rem; border-radius: 9999px; font-size: .8rem; font-weight: 700; line-height: 1.1; }
    .us-role svg { width: 1rem; height: 1rem; flex: none; }
    .us-r-admin { background: #FFE3EE; color: #E91E8C; }
    .us-r-operator { background: #DDF3C8; color: #2F6B12; }
    .us-r-customer { background: #E6EEFF; color: #2A5BD7; }
    .us-st { display: inline-flex; align-items: center; gap: .45rem; font-weight: 600; white-space: nowrap; }
    .us-st i { width: .6rem; height: .6rem; border-radius: 9999px; background: #22B14C; }
    .us-st.off i { background: #E5A00D; }
    .us-you { margin-left: .4rem; padding: .1rem .5rem; border-radius: 9999px; background: #E9EAE6; color: #565E4E; font-size: .68rem; font-weight: 700; vertical-align: middle; }
    .us-act { display: flex; align-items: center; justify-content: center; gap: .4rem; }
    .us-act .ct-act-detail { padding: .45rem 1rem; }
    .us-none { width: 2rem; }
    .sh-menu .us-mi { display: block; width: 100%; text-align: left; padding: .55rem .8rem; border: 0; border-radius: .7rem; background: transparent; font-size: .85rem; font-weight: 700; color: #2B3027; cursor: pointer; }
    .sh-menu .us-mi:hover { background: #FFF0F6; color: #E91E8C; }
</style>
@endpush

@section('content')
@php
    $total = $users->total();
    $from = $total ? $users->firstItem() : 0;
    $to = $total ? $users->lastItem() : 0;
    $perPage = $filters['per_page'];
    $meId = auth()->id();
    $reopen = old('_mode');
    $people = 'M15 19.13a9.37 9.37 0 0 0 4.12-.95 4.13 4.13 0 0 0-7.53-2.5M15 19.13v-.01a8.97 8.97 0 0 1-12 0v-.01m12 .02a6.38 6.38 0 0 0-12 .02M12 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.5 1.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z';
    $roleLabel = ['admin' => 'Admin', 'operator' => 'Operator', 'customer' => 'Customer'];
    $roleIco = [
        'admin' => '<path d="M12 3 4.5 6v5.5c0 4.5 3.1 8 7.5 9.5 4.4-1.5 7.5-5 7.5-9.5V6L12 3Z"/><path d="m9 12 2.2 2.2L15 10"/>',
        'operator' => '<path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.38a1.13 1.13 0 0 1-1.13-1.13V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.13c.62 0 1.12-.5 1.12-1.13V14.25m-17.25 0V6.38c0-.62.5-1.13 1.13-1.13h9.74c.62 0 1.13.5 1.13 1.13v11.62m0-11.62h2.9c.3 0 .58.12.8.33l3.3 3.3c.2.2.33.5.33.8v3.62"/>',
        'customer' => '<path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8.5a7 7 0 0 1 14 0"/>',
    ];
@endphp

{{-- Kepala halaman --}}
<section class="ct-hero">
    <div class="sh-title">
        <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $people }}"/></svg></span>
        <div>
            <h1>Users</h1>
            <p>Manage user data and system access rights.</p>
        </div>
    </div>

    <div class="ct-hero-art" aria-hidden="true">
        <svg viewBox="0 0 700 200" preserveAspectRatio="xMaxYMax meet" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="usHill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#CDEBA0"/><stop offset="1" stop-color="#A9DC74"/></linearGradient>
            </defs>
            <path d="M80 200 C180 150 300 150 400 170 C500 142 600 142 700 162 V200Z" fill="url(#usHill)"/>
            <path d="M240 200 C330 178 450 180 540 194 V200Z" fill="#B9E67F" opacity=".8"/>
            {{-- tiga orang --}}
            <g>
                <path d="M250 200 V150 C250 126 268 116 290 116 C312 116 330 126 330 150 V200Z" fill="#B79BE8"/>
                <circle cx="290" cy="92" r="24" fill="#FBD5B5"/><path d="M266 90 C266 66 314 66 314 90 C304 78 276 78 266 90Z" fill="#5B3A2E"/>
            </g>
            <g>
                <path d="M340 200 V138 C340 112 360 100 385 100 C410 100 430 112 430 138 V200Z" fill="#FFC9A0"/>
                <circle cx="385" cy="74" r="26" fill="#FBD5B5"/><path d="M359 72 C359 46 411 46 411 72 C400 58 370 58 359 72Z" fill="#7A4A2B"/>
            </g>
            <g>
                <path d="M440 200 V150 C440 126 458 116 480 116 C502 116 520 126 520 150 V200Z" fill="#5CC46B"/>
                <circle cx="480" cy="92" r="24" fill="#F4C79F"/><path d="M456 90 C456 62 504 62 504 90 C494 76 466 76 456 90Z" fill="#2B2F4A"/>
            </g>
            {{-- perisai --}}
            <g transform="translate(528 112)"><path d="M26 0 0 10v22c0 16 11 28 26 34 15-6 26-18 26-34V10L26 0Z" fill="#6DB83D"/><path d="m14 32 9 9 17-18" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></g>
            {{-- daun --}}
            <path d="M200 120 C186 96 196 76 222 70 C228 94 218 112 200 120Z" fill="#8FCF5E"/>
            <path d="M600 110 C590 86 600 66 626 62 C630 84 620 102 600 110Z" fill="#A5DB72"/>
            <path d="M170 60 C160 44 170 30 188 26 C192 42 184 56 170 60Z" fill="#FFB6D5"/>
        </svg>
    </div>

    @writable
    <button type="button" class="ct-add" data-open-add>
        <svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
        <span>Add User</span>
    </button>
    @endwritable
</section>

{{-- Kartu filter --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="sh-panel us-filter">
    <div class="us-row">
        <div>
            <label class="sh-lbl" for="f-name">Name</label>
            <input id="f-name" class="sh-fld" type="text" name="name" value="{{ $filters['name'] }}" placeholder="Search user name...">
        </div>
        <div>
            <label class="sh-lbl" for="f-email">Email</label>
            <input id="f-email" class="sh-fld" type="text" name="email" value="{{ $filters['email'] }}" placeholder="Search email...">
        </div>
        <div>
            <label class="sh-lbl" for="f-role">Role</label>
            <select id="f-role" class="sh-sel" name="role">
                <option value="">All</option>
                @foreach($roleLabel as $v => $l)<option value="{{ $v }}" @selected($filters['role'] === $v)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="sh-lbl" for="f-status">Status</label>
            <select id="f-status" class="sh-sel" name="status">
                <option value="">All</option>
                <option value="verified" @selected($filters['status'] === 'verified')>Verified</option>
                <option value="unverified" @selected($filters['status'] === 'unverified')>Unverified</option>
            </select>
        </div>
        <div class="us-btns">
            <button class="sh-btn sh-btn-apply" type="submit">
                <svg fill="currentColor" viewBox="0 0 24 24"><path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4Z"/></svg>
                <span>Apply Filters</span>
            </button>
            <a class="sh-btn sh-btn-reset" href="{{ route('admin.users.index') }}">
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
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $people }}"/></svg></span>
            <span>User list</span>
        </h2>
        <div class="sh-per">
            <label for="f-per">Show</label>:
            <select id="f-per" class="sh-sel" onchange="var u=new URL(location.href);u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location.href=u.toString()">
                @foreach([10, 25, 50] as $n)<option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} records</option>@endforeach
            </select>
            @if($users->onFirstPage())
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></span>
            @else
                <a class="sh-arrow" href="{{ $users->previousPageUrl() }}" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg></a>
            @endif
            @if($users->hasMorePages())
                <a class="sh-arrow" href="{{ $users->nextPageUrl() }}" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></a>
            @else
                <span class="sh-arrow sh-arrow-off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg></span>
            @endif
        </div>
    </div>

    <div class="sh-tbl-wrap">
        <table class="sh-tbl ct-tbl us-tbl">
            <thead>
                <tr>
                    <th style="width:4rem">No.</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th class="ct-c">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $i => $u)
                @php $me = $u->id === $meId; $verified = (bool) $u->email_verified_at; $r = in_array($u->role, \App\Models\User::ROLES, true) ? $u->role : 'customer'; @endphp
                <tr>
                    <td>{{ $users->firstItem() + $i }}</td>
                    <td class="ct-name" data-no-i18n>{{ $u->name }}@if($me)<span class="us-you">(you)</span>@endif</td>
                    <td data-no-i18n>{{ $u->email }}</td>
                    <td><span class="us-role us-r-{{ $r }}"><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $roleIco[$r] !!}</svg><span>{{ $roleLabel[$r] }}</span></span></td>
                    <td><span class="us-st {{ $verified ? '' : 'off' }}"><i></i><span>{{ $verified ? 'Verified' : 'Unverified' }}</span></span></td>
                    <td data-no-i18n style="white-space:nowrap">{{ $u->created_at?->format('d M Y, H:i') }}</td>
                    <td>
                        <div class="us-act"
                             data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}" data-role="{{ $r }}"
                             data-role-label="{{ $roleLabel[$r] }}" data-verified="{{ $verified ? 1 : 0 }}"
                             data-joined="{{ $u->created_at?->format('d M Y, H:i') }}" data-ships="{{ $u->shipments_count }}">
                            <button type="button" class="ct-act ct-act-detail" data-act="detail">
                                <svg fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span>Detail</span>
                            </button>
                            @unless($me || session('is_viewer') === true)
                            <details class="sh-kebab">
                                <summary aria-label="Action"><svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.7"/><circle cx="12" cy="12" r="1.7"/><circle cx="12" cy="19" r="1.7"/></svg></summary>
                                <div class="sh-menu" hidden>
                                    <button type="button" class="us-mi" data-act="role"><span>Change role</span></button>
                                </div>
                            </details>
                            @else
                                <span class="us-none"></span>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="sh-empty">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sh-foot">
        <span>Showing {{ $from }}–{{ $to }} of {{ $total }} records</span>
        @include('partials.pager', ['paginator' => $users])
    </div>
</div>

{{-- Dialog: tambah pengguna --}}
<dialog id="dlg-form" class="ct-dlg">
    <form method="POST" action="{{ route('admin.users.store') }}" autocomplete="off">
        @csrf
        <input type="hidden" name="_mode" value="add">
        <div class="ct-dlg-h">
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $people }}"/></svg></span>
            <h3>Add User</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <div><label class="sh-lbl" for="cf-name">Name *</label><input id="cf-name" class="sh-fld" name="name" required maxlength="255" value="{{ old('name') }}"></div>
            <div><label class="sh-lbl" for="cf-email">Email *</label><input id="cf-email" class="sh-fld" type="email" name="email" required maxlength="255" value="{{ old('email') }}"></div>
            <div class="us-g2">
                <div><label class="sh-lbl" for="cf-pass">Password * (min 8)</label><input id="cf-pass" class="sh-fld" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
                <div><label class="sh-lbl" for="cf-role">Role *</label>
                    <select id="cf-role" class="sh-sel" name="role">@foreach($roleLabel as $v => $l)<option value="{{ $v }}" @selected(old('role', 'customer') === $v)>{{ $l }}</option>@endforeach</select>
                </div>
            </div>
        </div>
        <div class="ct-dlg-f">
            <button type="button" class="sh-btn sh-btn-reset" data-close>Cancel</button>
            <button type="submit" class="sh-btn sh-btn-apply">Create user</button>
        </div>
    </form>
</dialog>

{{-- Dialog: ubah peran --}}
<dialog id="dlg-role" class="ct-dlg">
    <form method="POST" action="#" id="role-form">
        @csrf @method('PATCH')
        <div class="ct-dlg-h">
            <span class="ico"><svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 3 4.5 6v5.5c0 4.5 3.1 8 7.5 9.5 4.4-1.5 7.5-5 7.5-9.5V6L12 3Z"/><path d="m9 12 2.2 2.2L15 10"/></svg></span>
            <h3>Change role</h3>
            <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ct-dlg-b">
            <p class="ct-del-msg"><b id="rl-name" style="color:#1B1F18" data-no-i18n></b><br><span id="rl-email" data-no-i18n style="color:#565E4E;font-size:.85rem"></span></p>
            <div><label class="sh-lbl" for="rl-sel">Role *</label>
                <select id="rl-sel" class="sh-sel" name="role">@foreach($roleLabel as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
            </div>
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
        <h3>User detail</h3>
        <button type="button" class="ct-dlg-x" data-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    <div class="ct-dlg-b">
        <dl class="ct-dl">
            <dt>Name</dt><dd id="dt-name" data-no-i18n></dd>
            <dt>Email</dt><dd id="dt-email" data-no-i18n></dd>
            <dt>Role</dt><dd id="dt-role"></dd>
            <dt>Status</dt><dd id="dt-status"></dd>
            <dt>Joined</dt><dd id="dt-joined" data-no-i18n></dd>
            <dt>Shipments</dt><dd id="dt-ships" data-no-i18n></dd>
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
    var roleUrl = @json(route('admin.users.role', ['user' => '__ID__']));

    var addBtn = document.querySelector('[data-open-add]');   /* tidak ada di Mode Viewer */
    if (addBtn) { addBtn.addEventListener('click', function () { $('dlg-form').showModal(); $('cf-name').focus(); }); }

    document.querySelectorAll('.us-act [data-act]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = b.closest('.us-act').dataset, act = b.getAttribute('data-act');
            var det = b.closest('details'); if (det) { det.removeAttribute('open'); var m = det.querySelector('.sh-menu'); if (m) { m.hidden = true; } }
            if (act === 'detail') {
                $('dt-name').textContent = d.name; $('dt-email').textContent = d.email; $('dt-role').textContent = T(d.roleLabel);
                $('dt-status').textContent = T(d.verified === '1' ? 'Verified' : 'Unverified'); $('dt-joined').textContent = d.joined; $('dt-ships').textContent = d.ships;
                $('dlg-detail').showModal();
            } else if (act === 'role') {
                $('role-form').action = roleUrl.replace('__ID__', d.id);
                $('rl-name').textContent = d.name; $('rl-email').textContent = d.email; $('rl-sel').value = d.role;
                $('dlg-role').showModal();
            }
        });
    });

    document.querySelectorAll('dialog.ct-dlg').forEach(function (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('[data-close]')) { dlg.close(); } });
    });

    @if($reopen)
    $('dlg-form').showModal();
    @endif
})();
</script>
@endpush
