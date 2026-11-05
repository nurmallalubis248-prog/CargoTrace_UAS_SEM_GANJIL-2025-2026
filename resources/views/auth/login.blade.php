@extends('layouts.guest')
@section('title', 'Log in')
@section('content')
<h2 class="text-3xl font-extrabold tracking-tight text-matcha-900" data-i18n="loginTitle">Sign in to your account</h2>
<p class="mb-8 mt-2 text-sm text-ink-500" data-i18n="loginSub">Track every shipment, anywhere in the world.</p>
<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf
    <div>
        <label class="label" for="email" data-i18n="email">Email</label>
        <input class="input !py-3" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" required autofocus>
    </div>
    <div>
        <label class="label" for="password" data-i18n="password">Password</label>
        <input class="input !py-3" id="password" type="password" name="password" placeholder="••••••••" required>
    </div>
    <label class="flex items-center gap-2 text-sm text-ink-600"><input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-matcha-600"> <span data-i18n="remember">Remember me</span></label>
    <button class="grad-brand w-full rounded-xl py-3 text-sm font-bold text-white shadow-lg transition hover:opacity-90" type="submit" data-i18n="signin">Sign in</button>
    <p class="text-center text-sm text-ink-600"><span data-i18n="noAccount">No account?</span> <a class="font-semibold text-sakura-600 hover:underline" href="{{ route('register') }}" data-i18n="register">Register as customer</a></p>
</form>
@if(config('cargotrace.show_demo_accounts'))
    <div class="mt-8 rounded-xl border border-matcha-100 bg-matcha-50 p-4 text-xs text-ink-600">
        <p class="mb-1 font-bold text-matcha-800"><span data-i18n="demo">Demo accounts</span> (password: <code>password</code>)</p>
        <p>admin@cargotrace.test · operator@cargotrace.test · customer1@cargotrace.test</p>
    </div>
@endif
@endsection
