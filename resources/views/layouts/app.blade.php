<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | CargoTrace</title>
    @include('partials.theme')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @stack('head')
    <script>window.CT = window.CT || {};</script>
    <script src="{{ asset('js/ct-dict.js') }}?v={{ filemtime(public_path('js/ct-dict.js')) }}"></script>
    <script src="{{ asset('js/ct-i18n.js') }}?v={{ filemtime(public_path('js/ct-i18n.js')) }}"></script>
<style>
    /* Cards never overflow their grid cell (all pages) */
    .grid > * { min-width: 0; }
    .card { min-width: 0; }
    body { background: linear-gradient(135deg, #FFF3F8 0%, #FDEBF3 38%, #F1F8E6 100%) fixed !important; }
    body .card { background: rgba(255,255,255,.80); border: 1px solid rgba(255,255,255,.95); border-radius: 22px; box-shadow: 0 8px 28px -12px rgba(233,30,140,.22); }
    /* Sidebar CargoTrace: gradasi pink (atas) -> hijau (bawah), pill aktif putih, ilustrasi pesawat di dasar */
    aside.grad-side {
        background: linear-gradient(180deg, #FF72AE 0%, #FF8CB9 22%, #F2A79C 42%, #CDBF7F 58%, #ABD96B 78%, #9AD568 100%) !important;
        border-radius: 0 28px 28px 0;
        box-shadow: 0 10px 40px rgba(255, 114, 174, .28);
        overflow-y: auto; overflow-x: hidden;
        padding: 0 !important;
        scrollbar-width: none;
    }
    aside.grad-side::-webkit-scrollbar { display: none; }
    aside.grad-side .side-inner { padding: 1.25rem 1rem 0; }
    aside.grad-side .brand-sub { font-size: .7rem; font-weight: 500; letter-spacing: .02em; color: rgba(255,255,255,.92); }
    aside.grad-side .side-link {
        color: #fff; font-weight: 600; font-size: .95rem; gap: 1rem;
        padding: .6rem .85rem; border-radius: 9999px; position: relative;
    }
    aside.grad-side .side-link:hover { background: rgba(255,255,255,.18); color: #fff; }
    aside.grad-side .side-link svg { width: 1.6rem; height: 1.6rem; stroke-width: 1.7; }
    aside.grad-side .side-link-active {
        background: linear-gradient(90deg, #ffffff, #FFEAF3) !important;
        color: #E91E8C !important;
        box-shadow: 0 6px 16px rgba(233, 30, 140, .22);
        padding-top: .8rem; padding-bottom: .8rem;
    }
    aside.grad-side .side-link-active:hover { color: #E91E8C !important; }
    aside.grad-side [data-unread] {
        margin-left: auto; min-width: 1.6rem; height: 1.6rem; padding: 0 .4rem !important;
        display: inline-grid; place-items: center; border-radius: 9999px;
        background: #E91E8C !important; color: #fff; font-size: .8rem; font-weight: 800;
        box-shadow: 0 0 0 3px rgba(255,255,255,.35);
    }
    aside.grad-side p.side-section {
        display: flex; align-items: center; gap: .75rem; margin: 1.25rem 0 .5rem; padding: 0 .85rem;
        font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #fff;
    }
    aside.grad-side p.side-section::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.65); }
    aside.grad-side .side-user { display: flex; align-items: center; gap: .6rem; margin: 1rem 0 0; padding: .45rem .6rem; border-radius: 9999px; background: rgba(255,255,255,.22); backdrop-filter: blur(6px); }
    aside.grad-side .side-user .av { display: grid; place-items: center; width: 2rem; height: 2rem; flex: none; border-radius: 9999px; background: #fff; color: #E91E8C; font-weight: 800; font-size: .8rem; }
    aside.grad-side .side-user button { margin-left: auto; border-radius: 9999px; background: rgba(255,255,255,.92); color: #E91E8C; font-size: .7rem; font-weight: 800; padding: .3rem .7rem; }
    aside.grad-side .side-user button:hover { background: #fff; }
    aside.grad-side .side-art { position: relative; margin-top: auto; flex-shrink: 0; line-height: 0; pointer-events: none; }
    aside.grad-side .side-art svg { display: block; width: 100%; height: auto; }
    aside.grad-side .side-art p { position: absolute; left: 1.4rem; bottom: .9rem; right: 1rem; margin: 0; line-height: 1.3; color: #fff; font-style: italic; font-weight: 600; font-size: .95rem; text-shadow: 0 1px 6px rgba(60,120,30,.45); }
</style>
</head>
<body class="min-h-screen bg-matcha-50 text-ink-800">
@php
    $user = auth()->user(); $unread = $user ? $user->appNotifications()->whereNull('read_at')->count() : 0;
    // Ikon outline (sesuai desain sidebar): rumah, kotak, dokumen, lonceng, truk, pin, gudang, kalkulator, pengguna, riwayat
    $ic = [
        'dashboard' => 'M2.25 12 11.2 3.05a1.13 1.13 0 0 1 1.6 0L21.75 12M4.5 9.75V19.5a.75.75 0 0 0 .75.75H9.75v-4.5h4.5v4.5h4.5a.75.75 0 0 0 .75-.75V9.75',
        'shipments' => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'reports'   => 'M19.5 14.25v-2.63a3.38 3.38 0 0 0-3.38-3.37h-1.5A1.13 1.13 0 0 1 13.5 7.13v-1.5a3.38 3.38 0 0 0-3.38-3.38H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.63c-.62 0-1.13.5-1.13 1.13v17.25c0 .62.5 1.12 1.13 1.12h12.75c.62 0 1.12-.5 1.12-1.12V11.25a9 9 0 0 0-9-9Z',
        'notifications' => 'M14.86 17.08a23.85 23.85 0 0 0 5.45-1.31A8.97 8.97 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.97 8.97 0 0 1-2.31 6.02 23.85 23.85 0 0 0 5.45 1.31m5.72 0a24.26 24.26 0 0 1-5.72 0m5.72 0a3 3 0 1 1-5.72 0',
        'carriers'  => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.38a1.13 1.13 0 0 1-1.13-1.13V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.13c.62 0 1.12-.5 1.12-1.13V14.25m-17.25 0V6.38c0-.62.5-1.13 1.13-1.13h9.74c.62 0 1.13.5 1.13 1.13v11.62m0-11.62h2.9c.3 0 .58.12.8.33l3.3 3.3c.2.2.33.5.33.8v3.62',
        'locations' => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 0c0 7.5-9 11.25-9 11.25S3 18 3 10.5a9 9 0 1 1 18 0Z',
        'warehouses'=> 'M3 21V9.5L12 4l9 5.5V21M7 21v-7h10v7M7 17.5h10M10 14v7M14 14v7',
        'users'     => 'M15 19.13a9.37 9.37 0 0 0 4.12-.95 4.13 4.13 0 0 0-7.53-2.5M15 19.13v-.01a8.97 8.97 0 0 1-12 0v-.01m12 .02a6.38 6.38 0 0 0-12 .02M12 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.5 1.5a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z',
        'audit'     => 'M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7.5V12l3 2',
    ];
    // Label bawaan (Indonesia) bila kamus i18n belum punya kuncinya
    $labels = ['dashboard'=>'Dashboard','shipments'=>'Pengiriman','reports'=>'Laporan','notifications'=>'Notifikasi','carriers'=>'Pengangkut','locations'=>'Lokasi','warehouses'=>'Gudang','users'=>'Pengguna','audit'=>'Log Audit'];
    $links = [['dashboard','dashboard','dashboard','dashboard'], ['shipments','shipments.index','shipments.*','shipments'], ['reports','reports.index','reports.*','reports'], ['notifications','notifications.index','notifications.*','notifications']];
    $adminLinks = $user->isAdmin() ? [['carriers','admin.carriers.index','admin.carriers.*','carriers'], ['locations','admin.locations.index','admin.locations.*','locations'], ['warehouses','admin.warehouses.index','admin.warehouses.*','warehouses'], ['users','admin.users.index','admin.users.*','users'], ['audit','admin.audit.index','admin.audit.*','audit']] : [];
@endphp
<div class="flex min-h-screen">
    {{-- LEFT SIDEBAR: gradasi pink -> hijau (desain CargoTrace). Semua menu fitur ada di sini --}}
    <aside class="grad-side sticky top-0 hidden h-screen w-72 shrink-0 flex-col text-white lg:flex">
        <div class="side-inner">
            <a href="{{ route('dashboard') }}" class="mb-5 flex items-center gap-3 px-2 pt-1">
                <svg class="h-14 w-14 shrink-0 drop-shadow" fill="none" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/><path d="m7.5 5 9 5.2" stroke-width="1.1"/></svg>
                <span class="leading-tight">
                    <span class="block text-[1.7rem] font-extrabold tracking-tight">CargoTrace</span>
                    <span class="brand-sub block">Track • Ship • Deliver</span>
                </span>
            </a>
            <nav class="space-y-1.5">
                @foreach($links as [$key, $r, $pattern, $icon])
                    <a href="{{ route($r) }}" class="side-link {{ request()->routeIs($pattern) && !request()->routeIs('shipments.create') ? 'side-link-active' : '' }}">
                        <svg class="shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic[$icon] }}"/></svg>
                        <span data-i18n="{{ $key }}">{{ $labels[$key] }}</span>
                        @if($key === 'notifications' && $unread > 0)<span data-unread>{{ $unread }}</span>@endif
                    </a>
                @endforeach
            </nav>
            @if($adminLinks)
                @php $adminOpen = collect($adminLinks)->contains(fn ($l) => request()->routeIs($l[2])); @endphp
                <button type="button" id="admin-toggle" class="side-link mt-1.5 w-full" aria-expanded="true" aria-controls="admin-nav">
                    <svg class="shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></svg>
                    <span data-i18n="admin">Administrasi</span>
                    <svg id="admin-chev" class="!h-5 !w-5 ml-auto transition-transform" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <nav id="admin-nav" class="space-y-1">
                    @foreach($adminLinks as [$key, $r, $pattern, $icon])
                        <a href="{{ route($r) }}" class="side-link {{ request()->routeIs($pattern) ? 'side-link-active' : '' }}">
                            <svg class="shrink-0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic[$icon] }}"/></svg>
                            <span {!! in_array($key, ['warehouses']) ? '' : 'data-i18n="'.$key.'"' !!}>{{ $labels[$key] }}</span>
                        </a>
                    @endforeach
                </nav>
                <script>
                (function () {
                    var b = document.getElementById('admin-toggle'), n = document.getElementById('admin-nav'), c = document.getElementById('admin-chev');
                    var forceOpen = {{ $adminOpen ? 'true' : 'false' }}, open = true;
                    try { if (localStorage.getItem('ct_admin_open') === '0' && !forceOpen) { open = false; } } catch (e) {}
                    function paint() { n.style.display = open ? '' : 'none'; c.style.transform = open ? '' : 'rotate(-90deg)'; b.setAttribute('aria-expanded', open ? 'true' : 'false'); }
                    b.addEventListener('click', function () { open = !open; try { localStorage.setItem('ct_admin_open', open ? '1' : '0'); } catch (e) {} paint(); });
                    paint();
                })();
                </script>
            @endif
        </div>

        {{-- Ilustrasi dasar sidebar: pesawat pink di atas awan + bukit hijau + slogan --}}
        <div class="side-art" aria-hidden="true">
            <svg viewBox="0 0 288 200" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="ctHill1" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#B9E67F"/><stop offset="1" stop-color="#9AD568"/></linearGradient>
                    <linearGradient id="ctHill2" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#A2DA6C"/><stop offset="1" stop-color="#8CCB5B"/></linearGradient>
                </defs>
                <g fill="#FFD3E6" opacity=".9">
                    <ellipse cx="60" cy="112" rx="64" ry="26"/><ellipse cx="150" cy="122" rx="82" ry="30"/><ellipse cx="248" cy="82" rx="52" ry="34"/><ellipse cx="236" cy="116" rx="60" ry="26"/>
                </g>
                <g fill="#FFEEF6" opacity=".95">
                    <ellipse cx="34" cy="124" rx="40" ry="18"/><ellipse cx="92" cy="130" rx="46" ry="18"/><ellipse cx="200" cy="130" rx="50" ry="18"/>
                </g>
                <ellipse cx="38" cy="48" rx="13" ry="5" fill="#fff" opacity=".85"/><ellipse cx="46" cy="44" rx="8" ry="5" fill="#fff" opacity=".85"/>
                <g transform="rotate(-9 150 76)">
                    <path d="M154 66 L202 34 L211 37 L184 70Z" fill="#FFB1D3"/>
                    <path d="M74 80 C74 71 90 67 124 65 L204 56 C224 54 240 58 240 64 C240 72 224 75 204 76 L124 83 C92 85 74 87 74 80Z" fill="#FFCBE2"/>
                    <path d="M240 64 C240 59 230 56 218 56 L214 75 C228 74 240 71 240 64Z" fill="#FFE3F0"/>
                    <path d="M80 74 L56 46 L74 46 L98 70Z" fill="#FF8DBF"/>
                    <path d="M138 78 L40 104 L50 109 L158 86Z" fill="#FF8DBF"/>
                    <ellipse cx="124" cy="94" rx="15" ry="7.5" fill="#FF9FC8"/><ellipse cx="124" cy="94" rx="4" ry="6.5" fill="#E86AA6"/>
                    <ellipse cx="176" cy="84" rx="13" ry="6.5" fill="#FF9FC8"/><ellipse cx="176" cy="84" rx="3.5" ry="5.5" fill="#E86AA6"/>
                    <g fill="#6F78B8"><circle cx="104" cy="69" r="1.5"/><circle cx="112" cy="68" r="1.5"/><circle cx="120" cy="67" r="1.5"/><circle cx="128" cy="66.5" r="1.5"/><circle cx="136" cy="66" r="1.5"/><circle cx="144" cy="65" r="1.5"/><circle cx="152" cy="64.5" r="1.5"/><circle cx="160" cy="64" r="1.5"/><circle cx="168" cy="63" r="1.5"/><circle cx="176" cy="62.5" r="1.5"/></g>
                    <path d="M132 100 v9 M172 90 v8" stroke="#8A4A6C" stroke-width="2" stroke-linecap="round"/><circle cx="132" cy="111" r="3" fill="#6B3A55"/><circle cx="172" cy="100" r="2.6" fill="#6B3A55"/>
                </g>
                <path d="M0 138 C60 116 140 122 288 146 V200 H0Z" fill="url(#ctHill1)"/>
                <path d="M0 168 C70 146 170 156 288 174 V200 H0Z" fill="url(#ctHill2)"/>
                <path d="M0 188 C34 170 74 176 98 200 H0Z" fill="#FF9CC6" opacity=".85"/>
                <g><path d="M250 142 C238 126 244 110 258 106 C264 122 260 136 250 142Z" fill="#8FCF5E"/><path d="M270 146 C262 132 268 120 280 118 C282 130 278 140 270 146Z" fill="#7CC04F"/><path d="M14 150 C22 138 36 138 42 146 C34 154 22 156 14 150Z" fill="#7CC04F"/></g>
            </svg>
            <p data-i18n="tagline">Logistik lebih<br>terpercaya, lebih efisien.</p>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-[1000] border-b border-pink-100/70 bg-white/55 backdrop-blur-md">
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 lg:px-8">
                <a href="{{ route('dashboard') }}" class="text-lg font-extrabold lg:hidden">Cargo<span class="grad-text">Trace</span></a>
                <form action="{{ route('shipments.index') }}" method="GET" class="relative order-last w-full sm:order-none sm:w-auto sm:min-w-[16rem] sm:max-w-md sm:flex-1" role="search">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-500" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" name="q" placeholder="Cari nomor resi, pengiriman, atau lokasi..." class="w-full rounded-full border border-pink-100 bg-white/90 py-2.5 pl-10 pr-4 text-sm shadow-sm placeholder:text-ink-400 focus:border-sakura-300 focus:outline-none focus:ring-2 focus:ring-sakura-200">
                </form>
                <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                    <div class="mr-1 hidden text-right leading-tight sm:block">
                        <p id="top-clock" class="text-sm font-extrabold tabular-nums text-matcha-900">--:--:--</p>
                        <p id="top-date" class="whitespace-nowrap text-[11px] text-ink-500"></p>
                    </div>
                    <select id="country-sel" aria-label="Country" class="rounded-lg border border-ink-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink-700 focus:border-matcha-500 focus:outline-none" style="max-width:11rem"></select>
                    <select data-lang-select aria-label="Language" class="rounded-lg border border-ink-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink-700 focus:border-matcha-500 focus:outline-none"></select>
                    @if($user->isStaff())
                        <a href="{{ route('shipments.create') }}" class="btn-accent !py-1.5"><span data-i18n="newShipment">New shipment</span></a>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="relative grid h-10 w-10 place-items-center rounded-full bg-white/90 text-ink-800 shadow-sm hover:bg-white" aria-label="Notifikasi">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic['notifications'] }}"/></svg>
                        <span data-unread-top class="absolute -right-0.5 -top-0.5 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-[#E91E8C] px-1 text-[11px] font-extrabold text-white {{ $unread > 0 ? '' : 'hidden' }}">{{ $unread }}</span>
                    </a>
                    <details class="relative hidden md:block">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full bg-white/90 py-1 pl-1 pr-3 shadow-sm [&::-webkit-details-marker]:hidden">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-[#FF7DB5] to-[#8CCB4F] text-sm font-extrabold text-white">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                            <span class="leading-tight"><span class="block max-w-[8rem] truncate text-xs font-bold">{{ $user->name }}</span><span class="block text-[10px] text-ink-500">{{ ['admin' => 'Super Admin', 'operator' => 'Operator', 'customer' => 'Pelanggan'][strtolower($user->role)] ?? ucfirst($user->role) }}</span></span>
                            <svg class="h-4 w-4 text-ink-500" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <form method="POST" action="{{ route('logout') }}" class="absolute right-0 mt-2 w-40 rounded-2xl bg-white p-2 shadow-xl">@csrf
                            <button class="w-full rounded-xl px-3 py-2 text-left text-sm font-bold text-[#E91E8C] hover:bg-pink-50" data-i18n="logout">Log out</button>
                        </form>
                    </details>
                    <button id="install-btn" type="button" class="btn-light hidden whitespace-nowrap !px-3 !py-1.5 text-xs">Install app</button>
                    <form method="POST" action="{{ route('logout') }}" class="lg:hidden">@csrf<button class="btn-light !px-3 !py-1.5 text-xs" data-i18n="logout">Log out</button></form>
                </div>
            </div>
            
            <nav class="flex gap-1 overflow-x-auto px-3 py-2 lg:hidden">
                @foreach(array_merge($links, $adminLinks) as [$key, $r, $pattern, $icon])
                    <a href="{{ route($r) }}" class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold {{ request()->routeIs($pattern) ? 'grad-brand text-white' : 'bg-matcha-100 text-matcha-800' }}" {!! in_array($key, ['warehouses']) ? '' : 'data-i18n="'.$key.'"' !!}>{{ $labels[$key] }}</a>
                @endforeach
            </nav>
        </header>
        <script>
            (function () {
                const tick = () => {
                    const n = new Date();
                    document.getElementById('top-clock').textContent = CT.timeStr(n);
                    document.getElementById('top-date').textContent = CT.dateStr(n, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) + ' · ' + CT.tzLabel();
                };
                document.addEventListener('ct:country', tick);
                document.addEventListener('ct:lang', tick);
                document.addEventListener('DOMContentLoaded', () => { tick(); setInterval(tick, 1000); });
            })();
        </script>

        <main class="px-4 py-6 lg:px-8">
        @include('partials.sim-status')
        @if(session('success'))
        <div class="mb-4 rounded-lg border border-matcha-200 bg-matcha-50 px-4 py-3 text-sm text-matcha-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>

<div aria-hidden="true" class="pointer-events-none h-24 w-full opacity-80" style="background:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 1440 120%22 preserveAspectRatio=%22none%22><path d=%22M0 70 C240 20 480 100 720 60 C960 20 1200 90 1440 50 V120 H0Z%22 fill=%22%23CDEBA0%22 fill-opacity=%22.55%22/><path d=%22M0 95 C300 60 520 115 800 85 C1060 58 1250 110 1440 80 V120 H0Z%22 fill=%22%23FFC4DD%22 fill-opacity=%22.6%22/></svg>') bottom/100% 100% no-repeat"></div>
<footer class="px-4 pb-8 text-xs text-ink-500 lg:px-8">
    CargoTrace &middot; Web Programming 2 UAS &middot; All times are shown in UTC.
</footer>
</div>
</div>
<script>
(function () {
    // PDF links/forms carry the browser language (the server cannot know it).
    document.addEventListener('click', (e) => {
        const a = e.target.closest ? e.target.closest('a[data-pdf-lang]') : null; if (!a) { return; }
        let l = null; try { l = localStorage.getItem('ct_lang'); } catch (x) { /* ignore */ }
        if (l === 'id' || l === 'en') { const u = new URL(a.href, location.href); u.searchParams.set('lang', l); a.href = u.toString(); }
    }, true);

    // Live notifications: poll the unread counter and flash the badge when something new arrives (arrival, delay, exception, confirmation...).
    const link = document.querySelector('a[href$="/notifications"]'); if (!link) { return; }
    let last = {{ (int) ($unread ?? 0) }};
    const badge = () => {
        let b = link.querySelector('[data-unread]');
        if (!b) { b = document.createElement('span'); b.setAttribute('data-unread', ''); link.appendChild(b); }
        return b;
    };
    const toast = () => {
        const t = document.createElement('div');
        t.className = 'fixed bottom-4 right-4 z-50 rounded-xl border border-matcha-200 bg-white px-4 py-3 text-sm font-semibold text-matcha-900 shadow-lg';
        t.textContent = (window.CT && CT.tx) ? CT.tx('New notification') : 'New notification';
        document.body.appendChild(t); setTimeout(() => t.remove(), 5000);
    };
    setInterval(() => {
        if (document.hidden) { return; }
        fetch('{{ route('notifications.unread') }}', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then((r) => (r.ok ? r.json() : null))
            .then((d) => {
                if (!d) { return; }
                if (d.unread > last) { toast(); }
                last = d.unread; document.querySelectorAll('[data-unread-top]').forEach((t) => { t.textContent = d.unread; t.classList.toggle('hidden', d.unread === 0); }); const b = badge(); b.textContent = d.unread; b.classList.toggle('hidden', d.unread === 0);
            }).catch(() => {});
    }, 15000);
})();
</script>
@stack('scripts')
</body>
</html>
