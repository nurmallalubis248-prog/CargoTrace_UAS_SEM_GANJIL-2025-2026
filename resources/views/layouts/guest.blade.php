<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | CargoTrace</title>
    @include('partials.theme')
    <script src="{{ asset('js/ct-dict.js') }}?v={{ filemtime(public_path('js/ct-dict.js')) }}"></script>
    <script src="{{ asset('js/ct-i18n.js') }}?v={{ filemtime(public_path('js/ct-i18n.js')) }}"></script>
    <style>
        /* Layar lebar: gambar hero jadi latar penuh, form di area pink sebelah kanan.
           Gambar sudah memuat logo, tagline, dan tiga fitur, jadi panel brand lama tidak dipakai lagi. */
        @media (min-width: 1024px) {
            .login-stage {
                background: #fbe9f1 url('{{ asset('images/login-hero.jpg') }}') no-repeat left center / cover;
            }
            .login-card {
                background: rgba(255, 255, 255, .9);
                backdrop-filter: blur(8px);
                border-radius: 1.75rem;
                padding: 2rem 2.25rem;
                box-shadow: 0 24px 60px rgba(80, 110, 51, .20);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-white">
<div class="login-stage min-h-screen lg:flex lg:items-center lg:justify-end">
    <section class="flex items-center justify-center p-6 sm:p-12 lg:w-[46%] lg:p-10 xl:pr-24">
        <div class="login-card w-full max-w-md">
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
            <p class="mt-6 text-center text-xs text-ink-500">© CargoTrace · Web Programming 2 UAS</p>
        </div>
    </section>
</div>
</body>
</html>