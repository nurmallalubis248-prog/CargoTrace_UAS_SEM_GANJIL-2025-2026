@extends('layouts.guest')
@section('title', 'Register')
@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="name">Name</label>
        <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div>
        <label class="label" for="password">Password (min. 8 characters)</label>
        <input class="input" id="password" type="password" name="password" required>
    </div>
    <div>
        <label class="label" for="password_confirmation">Confirm password</label>
        <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
    </div>
    <button class="btn" type="submit">Create customer account</button>
    <p class="text-center text-sm text-ink-600">Already registered? <a class="text-matcha-700 underline" href="{{ route('login') }}">Log in</a></p>
</form>
@endsection
