<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | CargoTrace</title>
    @include('partials.theme')
    <script src="{{ asset('js/ct-dict.js') }}?v={{ filemtime(public_path('js/ct-dict.js')) }}"></script>
    <script src="{{ asset('js/ct-i18n.js') }}?v={{ filemtime(public_path('js/ct-i18n.js')) }}"></script>
    @hasSection('bare')
        {{-- Landing page tamu (login / register): font judul + stylesheet khusus --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap">
        <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
    @endif
    <style>
        /* Pill User/Bahasa (pojok kanan atas) bersifat fixed: sisakan ruang di atas konten agar tidak tertimpa. */
        .login-stage .login-form-col { padding-top: 5rem; }
        /* Layar lebar: gambar hero (penuh, 16:9) jadi latar yang diam (fixed), halaman tetap bisa digulir.
           Form login berupa kartu kaca (frosted glass) di sisi kanan, di atas globe/kota pada gambar. */
        @media (min-width: 1024px) {
            .login-stage {
                min-height: 100vh;
                background: #fdeef4 url('{{ asset('images/login-hero.jpg') }}?v={{ filemtime(public_path('images/login-hero.jpg')) }}') no-repeat left center / cover fixed;
            }
            .login-form-col {
                /* lebar gambar yang tampil = max(lebar layar, tinggi layar x 16/9); kartu form dimulai di 66% dari lebar itu */
                margin-left: min(max(66vw, calc(100vh * 1.7778 * 0.66)), calc(100vw - 30rem));
            }
            .login-stage .login-form-col { padding-top: 5.5rem; padding-bottom: 2.5rem; }
            .login-card {
                padding: 2rem 2.25rem;
                border-radius: 1.5rem;
                border: 1px solid rgba(255, 255, 255, .7);
                background: rgba(255, 255, 255, .82);
                -webkit-backdrop-filter: blur(14px) saturate(1.1);
                backdrop-filter: blur(14px) saturate(1.1);
                box-shadow: 0 20px 50px -12px rgba(219, 39, 119, .25);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-white">
@include('partials.auth-pill')
<div class="@hasSection('bare') landing-stage @else login-stage min-h-screen @endif">
@hasSection('bare')
    {{-- Halaman tanpa kartu (login / register): landing page penuh yang bisa di-scroll. Form login & daftar ada di popup (partials/auth-pill). --}}
    @yield('content')
@else
    <section class="login-form-col flex min-h-screen p-6 sm:p-12 lg:px-10 lg:py-0">
        {{-- m-auto: berada di tengah kalau muat, dan mulai dari atas (bisa digulir) kalau lebih tinggi dari layar --}}
        <div class="login-inner login-card m-auto w-full max-w-md">
            <div class="mb-8 lg:hidden"><span class="text-3xl font-extrabold">Cargo<span class="grad-text">Trace</span></span></div>
            @if($errors->any() && ! session('login_failed') && ! session('register_failed'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
            <p class="mt-6 text-center text-xs text-ink-500">© CargoTrace · Web Programming 2 UAS</p>
        </div>
    </section>
@endif
</div>
@hasSection('bare')
    <script src="{{ asset('js/landing.js') }}?v={{ filemtime(public_path('js/landing.js')) }}" defer></script>
@endif
</body>
</html>
