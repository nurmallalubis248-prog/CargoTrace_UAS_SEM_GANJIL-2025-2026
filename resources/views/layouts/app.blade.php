<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | CargoTrace</title>
    @include('partials.theme')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @stack('head')
</head>
<body class="min-h-screen bg-matcha-50 text-ink-800">
@php $user = auth()->user(); $unread = $user ? $user->appNotifications()->whereNull('read_at')->count() : 0; @endphp
<header class="bg-matcha-900 text-white shadow-md">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 text-lg font-extrabold tracking-tight">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-sakura-400 text-sm text-white">CT</span>
            <span>Cargo<span class="text-sakura-300">Trace</span></span>
        </a>
        @php
            $links = [
                ['dashboard', 'Dashboard', 'dashboard'],
                ['shipments.index', 'Shipments', 'shipments.*'],
            ];
            if ($user->isAdmin()) {
                $links[] = ['admin.carriers.index', 'Carriers', 'admin.carriers.*'];
                $links[] = ['admin.locations.index', 'Locations', 'admin.locations.*'];
                $links[] = ['admin.users.index', 'Users', 'admin.users.*'];
                $links[] = ['admin.audit.index', 'Audit log', 'admin.audit.*'];
            }
        @endphp
        <nav class="flex flex-wrap items-center gap-1">
            @foreach($links as [$r, $label, $pattern])
                <a href="{{ route($r) }}" class="nav-link {{ request()->routeIs($pattern) && !request()->routeIs('shipments.create') ? 'nav-link-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex flex-wrap items-center gap-3 text-sm">
            @if($user->isStaff())
                <a href="{{ route('shipments.create') }}" class="btn-accent !py-1.5">New shipment</a>
            @endif
            <a href="{{ route('notifications.index') }}" class="nav-link">
                Notifications
                @if($unread > 0)
                    <span class="ml-1 rounded-full bg-sakura-400 px-1.5 py-0.5 text-xs font-bold text-white">{{ $unread }}</span>
                @endif
            </a>
            <span class="flex items-center gap-2 text-matcha-100">{{ $user->name }}
                <span class="rounded-full bg-matcha-700 px-2 py-0.5 text-xs font-semibold capitalize text-matcha-100">{{ strtolower($user->role) }}</span>
            </span>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="rounded-lg border border-matcha-600 px-3 py-1.5 text-xs font-medium text-matcha-100 hover:bg-matcha-800">Log out</button>
            </form>
        </div>
    </div>
    <div class="h-1 bg-gradient-to-r from-matcha-500 via-sakura-300 to-sakura-400"></div>
</header>

<main class="mx-auto max-w-7xl px-4 py-6">
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

<footer class="mx-auto max-w-7xl px-4 pb-8 text-xs text-ink-500">
    CargoTrace &middot; Web Programming 2 UAS &middot; All times are shown in UTC.
</footer>
@stack('scripts')
</body>
</html>
