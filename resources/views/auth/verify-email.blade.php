@extends('layouts.guest')
@section('title', 'Verify email')
@section('content')
<div class="space-y-4">
    <h1 class="text-xl font-bold">Verify your email</h1>
    <p class="text-sm text-ink-600">
        We sent a verification link to <b>{{ auth()->user()->email }}</b>.
        Open your inbox (check Spam too), click the link, then you can use CargoTrace.
    </p>

    @if(session('status') === 'verification-link-sent')
        <div class="rounded-lg border border-matcha-200 bg-matcha-50 px-3 py-2 text-sm text-matcha-800">A new verification link has been sent.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">@csrf
        <button class="btn" type="submit">Resend verification email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">@csrf
        <button class="text-sm text-matcha-700 underline" type="submit">Log out</button>
    </form>
</div>
@endsection
