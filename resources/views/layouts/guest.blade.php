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
        /* Layar lebar: gambar hero jadi latar, dan form login menempati area pink KOSONG di sisi kanan gambar
           (tanpa kartu melayang). Area kosong itu mulai di sekitar 62,5% lebar gambar. */
        @media (min-width: 1024px) {
            .login-stage {
                height: 100vh; overflow: hidden;
                background: #fdeef4 url('{{ asset('images/login-hero.jpg') }}') no-repeat left center / cover;
            }
            .login-form-col {
                height: 100%; overflow-y: auto;
                /* lebar gambar yang tampil = max(lebar layar, tinggi layar x 16/9); form dimulai di 62,5% dari lebar itu */
                margin-left: min(max(62.5vw, calc(100vh * 1.7778 * 0.625)), calc(100vw - 26rem));
            }
        }
    </style>
</head>
<body class="min-h-screen bg-white">
<div class="login-stage min-h-screen">
    <section class="login-form-col flex min-h-screen items-center justify-center p-6 sm:p-12 lg:min-h-0 lg:p-10">
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
            <p class="mt-6 text-center text-xs text-ink-500">© CargoTrace · Web Programming 2 UAS</p>
        </div>
    </section>
</div>
</body>
</html>