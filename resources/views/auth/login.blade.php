@extends('layouts.guest')
@section('title', 'Log in')
@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <input class="input" id="password" type="password" name="password" required>
    </div>
    <label class="flex items-center gap-2 text-sm text-ink-600">
        <input type="checkbox" name="remember" value="1"> Remember me
    </label>
    <button class="btn" type="submit">Log in</button>
    <p class="text-center text-sm text-ink-600">No account? <a class="text-matcha-700 underline" href="{{ route('register') }}">Register as customer</a></p>
</form>

@if(config('cargotrace.show_demo_accounts'))
    <div class="mt-5 rounded-lg bg-ink-50 p-3 text-xs text-ink-600">
        <p class="mb-1 font-semibold">Demo accounts (password: <code>password</code>)</p>
        <p>admin@cargotrace.test &middot; operator@cargotrace.test &middot; customer1@cargotrace.test</p>
    </div>
@endif
@endsection
