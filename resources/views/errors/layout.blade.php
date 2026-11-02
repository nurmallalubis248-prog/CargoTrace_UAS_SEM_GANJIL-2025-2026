<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') | CargoTrace</title>
    @include('partials.theme')
</head>
<body class="flex min-h-screen items-center justify-center bg-matcha-50 p-4">
    <div class="max-w-md rounded-2xl border border-matcha-100 border-t-4 border-t-sakura-400 bg-white p-8 text-center shadow-lg">
        <p class="text-6xl font-extrabold text-matcha-700">@yield('code')</p>
        <h1 class="mt-2 text-xl font-bold text-ink-800">@yield('title')</h1>
        <p class="mt-2 text-sm text-ink-600">@yield('message')</p>
        <a href="{{ url('/') }}" class="btn mt-6">Back to home</a>
    </div>
</body>
</html>
