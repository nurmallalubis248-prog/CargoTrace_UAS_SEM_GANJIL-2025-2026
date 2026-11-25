@extends('layouts.guest')
@section('title', 'Verify email')
@section('bare', '1')
{{-- Halaman landing (blur) + popup verifikasi email, gayanya sama dengan popup pendaftaran (partials/auth-pill). --}}
@section('content')
@php
    $email = auth()->user()->email;
    $isGmail = preg_match('/@(gmail|googlemail)\.com$/i', $email);
@endphp
@include('auth._landing')

<style>
    .ct-pill { z-index: 70; } /* pemilih bahasa tetap bisa dipakai di atas popup */
    #verify-modal .ct-modal-backdrop { background: rgba(38,51,26,.38); -webkit-backdrop-filter: blur(9px); backdrop-filter: blur(9px); }
    #verify-modal .ct-modal-card { max-width: 28rem; }
</style>

<div id="verify-modal" class="ct-modal" role="dialog" aria-modal="true" aria-labelledby="verify-title">
    <div class="ct-modal-backdrop"></div>
    <div class="ct-modal-card">
        <div class="ct-modal-ic">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
        </div>
        <h3 id="verify-title" class="mt-3 text-2xl font-extrabold tracking-tight text-matcha-900">Verify your email</h3>
        <p class="mt-1 text-sm text-ink-500">We sent a verification link to</p>
        <div class="mt-2 inline-flex max-w-full items-center rounded-full border border-matcha-200 bg-matcha-50 px-3.5 py-1 text-sm font-bold text-matcha-800">
            <span class="truncate" data-no-i18n>{{ $email }}</span>
        </div>

        {{-- Langkah --}}
        <ol class="mt-5 grid grid-cols-3 text-center text-[11px]" aria-label="Progress">
            <li class="flex flex-col items-center gap-1">
                <span class="grid h-6 w-6 place-items-center rounded-full bg-matcha-600 text-white"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg></span>
                <span class="font-semibold text-matcha-700">Account created</span>
            </li>
            <li class="relative flex flex-col items-center gap-1 before:absolute before:right-1/2 before:top-3 before:h-0.5 before:w-full before:bg-matcha-600">
                <span class="relative grid h-6 w-6 place-items-center rounded-full bg-sakura-400 text-xs font-bold text-white ring-4 ring-sakura-100">2</span>
                <span class="font-semibold text-sakura-700">Verify email</span>
            </li>
            <li class="relative flex flex-col items-center gap-1 before:absolute before:right-1/2 before:top-3 before:h-0.5 before:w-full before:bg-ink-200">
                <span class="relative grid h-6 w-6 place-items-center rounded-full bg-ink-100 text-xs font-bold text-ink-500">3</span>
                <span class="font-semibold text-ink-500">Start tracking</span>
            </li>
        </ol>

        @if(session('status') === 'verification-link-sent')
            <div class="mt-4 rounded-lg border border-matcha-200 bg-matcha-50 px-3 py-2 text-sm text-matcha-800" role="status">A new verification link has been sent.</div>
        @endif
        @if(session('mail_failed'))
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">The email could not be sent. Check the SMTP settings (.env), then press Resend.</div>
        @endif

        <div class="mt-5 space-y-2.5">
            @if($isGmail)
                <a href="https://mail.google.com/" target="_blank" rel="noopener" class="grad-brand block w-full rounded-xl py-3 text-center text-sm font-bold text-white shadow-lg transition hover:opacity-90">Open Gmail</a>
            @endif
            <form method="POST" action="{{ route('verification.send') }}" id="resend-form">@csrf
                <button id="resend-btn" type="submit"
                    class="{{ $isGmail ? 'border border-matcha-200 bg-white text-matcha-800 hover:bg-matcha-50' : 'grad-brand text-white shadow-lg hover:opacity-90' }} w-full rounded-xl py-3 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-60">
                    <span>Resend verification email</span><span id="resend-wait" class="ml-1 hidden"></span>
                </button>
            </form>
        </div>

        <div class="mt-4 rounded-xl border border-ink-200 bg-ink-50/70 px-4 py-3 text-xs leading-relaxed text-ink-600">
            <p class="font-semibold text-ink-700">Did not receive the email?</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                <li>Check your Spam or Promotions folder.</li>
                <li>Make sure the email address above is correct.</li>
                <li>The link is valid for 60 minutes.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf
            <button class="text-sm font-semibold text-matcha-700 hover:underline" type="submit">Log out</button>
        </form>
    </div>
</div>

<script>
(function () {
    document.body.style.overflow = 'hidden'; // halaman di belakang popup tidak bisa digulir
    var first = document.querySelector('#verify-modal a, #verify-modal button'); if (first) { first.focus(); }

    // Jeda 60 detik di tombol kirim ulang (server juga membatasi 3x per menit).
    var KEY = 'ct_resend_until', btn = document.getElementById('resend-btn'), wait = document.getElementById('resend-wait'), form = document.getElementById('resend-form');
    function get() { try { return parseInt(localStorage.getItem(KEY) || '0', 10); } catch (e) { return 0; } }
    function set(v) { try { localStorage.setItem(KEY, String(v)); } catch (e) {} }
    @if(session('status') === 'verification-link-sent' || str_contains((string) request()->headers->get('referer'), '/register') || str_contains((string) request()->headers->get('referer'), '/login'))
    if (get() < Date.now()) { set(Date.now() + 60000); }
    @endif
    function tick() {
        var left = Math.ceil((get() - Date.now()) / 1000);
        if (left > 0) { btn.disabled = true; wait.textContent = '(' + left + 's)'; wait.classList.remove('hidden'); setTimeout(tick, 500); }
        else { btn.disabled = false; wait.classList.add('hidden'); }
    }
    form.addEventListener('submit', function () { set(Date.now() + 60000); });
    tick();
})();
</script>
@endsection
