<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | CargoTrace</title>
    @include('partials.theme')
</head>
<body class="flex min-h-screen items-center justify-center bg-gradient-to-br from-matcha-900 via-matcha-800 to-matcha-700 p-4">
<div class="w-full max-w-md">
    <div class="mb-6 text-center">
        <span class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-sakura-400 text-xl font-extrabold text-white shadow-lg">CT</span>
        <h1 class="text-3xl font-extrabold tracking-tight text-white">Cargo<span class="text-sakura-300">Trace</span></h1>
        <p class="mt-1 text-sm text-matcha-100">Global shipment visibility &amp; tracking</p>
    </div>
    <div class="rounded-2xl border-t-4 border-sakura-400 bg-white p-7 shadow-2xl">
        @if($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </div>
</div>
</body>
</html>
