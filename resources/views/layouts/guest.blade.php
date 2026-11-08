<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | CargoTrace</title>
    @include('partials.theme')
    <script src="{{ asset('js/ct-dict.js') }}?v={{ filemtime(public_path('js/ct-dict.js')) }}"></script>
    <script src="{{ asset('js/ct-i18n.js') }}?v={{ filemtime(public_path('js/ct-i18n.js')) }}"></script>
</head>
<body class="min-h-screen bg-white">
<div class="grid min-h-screen lg:grid-cols-2">
    {{-- Brand panel: matcha -> pink gradient --}}
    <section class="grad-brand relative hidden overflow-hidden p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-white/10"></div>
        <div class="relative flex items-center gap-3">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-lg font-extrabold text-matcha-800 shadow-lg">CT</span>
            <span class="text-2xl font-extrabold tracking-tight">CargoTrace</span>
        </div>
        <div class="relative">
            <h1 class="max-w-md text-4xl font-extrabold leading-tight" data-i18n="tagline">Global shipment visibility &amp; tracking</h1>
            <ul class="mt-8 space-y-4 text-matcha-50">
                @foreach(['f1','f2','f3'] as $f)
                    <li class="flex items-center gap-3"><span class="grid h-7 w-7 place-items-center rounded-full bg-white/25 text-sm">✓</span><span data-i18n="{{ $f }}"></span></li>
                @endforeach
            </ul>
        </div>
        <p class="relative text-xs text-white/80">© CargoTrace · Web Programming 2 UAS</p>
    </section>

    <section class="flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md">
            <div class="mb-4 flex justify-end">
                <select data-lang-select aria-label="Language" class="rounded-lg border border-ink-300 bg-white px-2 py-1.5 text-xs font-semibold text-ink-700 focus:border-matcha-500 focus:outline-none"></select>
            </div>
            <div class="mb-8 lg:hidden"><span class="text-3xl font-extrabold">Cargo<span class="grad-text">Trace</span></span></div>
            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
    </section>
</div>
</body>
</html>
