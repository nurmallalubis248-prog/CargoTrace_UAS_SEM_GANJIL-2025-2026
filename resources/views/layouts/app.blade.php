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
    <script>window.CT = window.CT || {}; CT.currencies = @json(config('cargotrace.currencies'));</script>
    <script src="{{ asset('js/ct-dict.js') }}?v={{ filemtime(public_path('js/ct-dict.js')) }}"></script>
    <script src="{{ asset('js/ct-i18n.js') }}?v={{ filemtime(public_path('js/ct-i18n.js')) }}"></script>
<style>
    /* Money cards: never overflow their card (all pages) */
    .grid > * { min-width: 0; }
    .card { min-width: 0; }
    p[data-idr] { font-size: clamp(1rem, 0.6rem + 1.1vw, 1.5rem); line-height: 1.25; overflow-wrap: anywhere; font-variant-numeric: tabular-nums; }
    /* Sidebar bergambar: hijau matcha ke pink, pesawat + globe di atas, truk + kapal di bawah */
    aside.grad-side {
        background:
            url('{{ asset('images/side-bottom.png') }}') no-repeat bottom center / 100% auto,
            url('{{ asset('images/side-top.png') }}') no-repeat top right / auto 200px,
            linear-gradient(180deg, #6A9B42 0%, #70A146 45%, #8FB85C 72%, #F2C4D6 100%) !important;
        padding-bottom: 5.75rem !important;   /* kotak pengguna/logout tetap di atas gambar truk */
    }
</style>
</head>
<body class="min-h-screen bg-matcha-50 text-ink-800">
@php
    $user = auth()->user(); $unread = $user ? $user->appNotifications()->whereNull('read_at')->count() : 0;
    $ic = [
        'dashboard' => 'M3 13h8V3H3zm0 8h8v-6H3zm10 0h8V11h-8zm0-18v6h8V3z',
        'shipments' => 'M3 7l9-4 9 4v10l-9 4-9-4zM3 7l9 4 9-4M12 11v10',
        'carriers'  => 'M3 16V6h11v10M14 9h4l3 3v4h-7M7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z',
        'locations' => 'M12 21s7-6.2 7-11a7 7 0 10-14 0c0 4.8 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'users'     => 'M16 11a4 4 0 10-8 0 4 4 0 008 0zM4 21a8 8 0 0116 0',
        'audit'     => 'M9 12h6M9 16h6M7 3h7l5 5v13H7zM14 3v5h5',
        'notifications' => 'M18 16v-5a6 6 0 10-12 0v5l-2 2h16zM10 21h4',
    ];
    $links = [['dashboard','dashboard','dashboard','dashboard'], ['shipments','shipments.index','shipments.*','shipments'], ['reports','reports.index','reports.*','audit']];
    $adminLinks = $user->isAdmin() ? [['carriers','admin.carriers.index','admin.carriers.*','carriers'], ['locations','admin.locations.index','admin.locations.*','locations'], ['warehouses','admin.warehouses.index','admin.warehouses.*','carriers'], ['accounting','admin.accounting.index','admin.accounting.*','audit'], ['users','admin.users.index','admin.users.*','users'], ['audit','admin.audit.index','admin.audit.*','audit']] : [];
    $links[] = ['notifications','notifications.index','notifications.*','notifications'];
    $renderLink = function ($l) use ($ic) { return $l; };
@endphp
<div class="flex min-h-screen">
    {{-- LEFT SIDEBAR: all feature menus live here --}}
    <aside class="grad-side sticky top-0 hidden h-screen w-64 shrink-0 flex-col p-4 text-white shadow-2xl lg:flex">
        <a href="{{ route('dashboard') }}" class="mb-6 flex items-center gap-3 px-2 pt-1">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/95 text-sm font-extrabold text-matcha-800 shadow">CT</span>
            <span class="text-xl font-extrabold tracking-tight">Cargo<span class="text-sakura-200">Trace</span></span>
        </a>
        <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-widest text-matcha-200" data-i18n="menu">Main menu</p>
        <nav class="space-y-1">
            @foreach($links as [$key, $r, $pattern, $icon])
                <a href="{{ route($r) }}" class="side-link {{ request()->routeIs($pattern) && !request()->routeIs('shipments.create') ? 'side-link-active' : '' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic[$icon] }}"/></svg>
                    <span {!! in_array($key, ['warehouses', 'accounting']) ? '' : 'data-i18n="'.$key.'"' !!}>{{ ucfirst($key) }}</span>
                    @if($key === 'notifications' && $unread > 0)<span data-unread class="ml-auto rounded-full bg-sakura-400 px-2 py-0.5 text-xs font-bold text-white">{{ $unread }}</span>@endif
                </a>
            @endforeach
        </nav>
        @if($adminLinks)
            <p class="mb-2 mt-6 px-3 text-[11px] font-bold uppercase tracking-widest text-matcha-200" data-i18n="admin">Administration</p>
            <nav class="space-y-1">
                @foreach($adminLinks as [$key, $r, $pattern, $icon])
                    <a href="{{ route($r) }}" class="side-link {{ request()->routeIs($pattern) ? 'side-link-active' : '' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $ic[$icon] }}"/></svg>
                        <span {!! in_array($key, ['warehouses', 'accounting']) ? '' : 'data-i18n="'.$key.'"' !!}>{{ ucfirst($key) }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
        <div class="mt-auto rounded-2xl bg-white/15 p-3 backdrop-blur">
            <p class="truncate text-sm font-bold">{{ $user->name }}</p>
            <p class="mb-3 text-xs capitalize text-matcha-100">{{ strtolower($user->role) }}</p>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="w-full rounded-lg bg-white/90 px-3 py-2 text-xs font-bold text-matcha-800 hover:bg-white" data-i18n="logout">Log out</button>
            </form>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-[1000] border-b border-matcha-100 bg-white/85 backdrop-blur">
            <div class="flex flex-wrap items-center gap-3 px-4 py-3 lg:px-8">
                <a href="{{ route('dashboard') }}" class="text-lg font-extrabold lg:hidden">Cargo<span class="grad-text">Trace</span></a>
                <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                    <div class="mr-1 hidden text-right leading-tight sm:block">
                        <p id="top-clock" class="text-sm font-extrabold tabular-nums text-matcha-900">--:--:--</p>
                        <p id="top-date" class="whitespace-nowrap text-[11px] text-ink-500"></p>
                    </div>
                    <select id="country-sel" aria-label="Country" class="rounded-lg border border-ink-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink-700 focus:border-matcha-500 focus:outline-none" style="max-width:11rem"></select>
                    <select id="cur-sel" aria-label="Currency" class="rounded-lg border border-ink-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink-700 focus:border-matcha-500 focus:outline-none"></select>
                    <select data-lang-select aria-label="Language" class="rounded-lg border border-ink-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink-700 focus:border-matcha-500 focus:outline-none"></select>
                    @if($user->isStaff())
                        <a href="{{ route('shipments.create') }}" class="btn-accent !py-1.5"><span data-i18n="newShipment">New shipment</span></a>
                    @endif
                    <button id="install-btn" type="button" class="btn-light hidden whitespace-nowrap !px-3 !py-1.5 text-xs">Install app</button>
                    <form method="POST" action="{{ route('logout') }}" class="lg:hidden">@csrf<button class="btn-light !px-3 !py-1.5 text-xs" data-i18n="logout">Log out</button></form>
                </div>
            </div>
            <div class="h-1 grad-brand"></div>
            <nav class="flex gap-1 overflow-x-auto px-3 py-2 lg:hidden">
                @foreach(array_merge($links, $adminLinks) as [$key, $r, $pattern, $icon])
                    <a href="{{ route($r) }}" class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold {{ request()->routeIs($pattern) ? 'grad-brand text-white' : 'bg-matcha-100 text-matcha-800' }}" {!! in_array($key, ['warehouses', 'accounting']) ? '' : 'data-i18n="'.$key.'"' !!}>{{ ucfirst($key) }}</a>
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

    // Live notifications: poll the unread counter and flash the badge when something new arrives (payment, arrival, confirmation...).
    const link = document.querySelector('a[href$="/notifications"]'); if (!link) { return; }
    let last = {{ (int) ($unread ?? 0) }};
    const badge = () => {
        let b = link.querySelector('[data-unread]');
        if (!b) { b = document.createElement('span'); b.setAttribute('data-unread', ''); b.className = 'ml-auto rounded-full bg-sakura-400 px-2 py-0.5 text-xs font-bold text-white'; link.appendChild(b); }
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
                last = d.unread; const b = badge(); b.textContent = d.unread; b.classList.toggle('hidden', d.unread === 0);
            }).catch(() => {});
    }, 15000);
})();
</script>
@stack('scripts')
</body>
</html>
