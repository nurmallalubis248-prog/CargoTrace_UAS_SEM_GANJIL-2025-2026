@extends('layouts.app')
@section('title', 'Locations')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Locations</h1>
<form method="POST" action="{{ route('admin.locations.store') }}" class="card mb-4">
    @csrf
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <div class="md:col-span-2"><label class="label">Name *</label><input class="input" id="loc-name" name="name" required value="{{ old('name') }}"></div>
        <div><label class="label">Code * (max 10)</label><input class="input" name="code" required maxlength="10" value="{{ old('code') }}"></div>
        <div><label class="label">Country * (ISO-2)</label><input class="input" name="country" required maxlength="2" value="{{ old('country') }}"></div>
        <div><label class="label">Type *</label>
            <select class="input" name="type">@foreach(\App\Models\Location::TYPES as $t)<option value="{{ $t }}" @selected(old('type') === $t)>{{ $t }}</option>@endforeach</select></div>
        <div><label class="label">Latitude *</label><input class="input" id="loc-lat" type="number" step="any" name="latitude" required value="{{ old('latitude') }}"></div>
        <div><label class="label">Longitude *</label><input class="input" id="loc-lng" type="number" step="any" name="longitude" required value="{{ old('longitude') }}"></div>
        <div class="flex items-end gap-2">
            <button class="btn-light" type="button" id="geocode-btn">Look up coordinates</button>
            <button class="btn" type="submit">Add</button>
        </div>
    </div>
    <p id="geocode-msg" class="mt-2 text-xs text-ink-500">Look-up uses OpenStreetMap Nominatim (external API). If it fails, enter coordinates manually.</p>
</form>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50"><tr><th class="th">Name</th><th class="th">Code</th><th class="th">Country</th><th class="th">Type</th><th class="th">Lat / Lng</th><th class="th">Active</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-ink-100">
        @foreach($locations as $l)
            <tr>
                <td class="td font-medium">{{ $l->name }}</td><td class="td">{{ $l->code }}</td><td class="td">{{ $l->country }}</td>
                <td class="td">{{ $l->type }}</td><td class="td">{{ $l->latitude }}, {{ $l->longitude }}</td>
                <td class="td">{{ $l->is_active ? 'Yes' : 'No' }}</td>
                <td class="td"><form method="POST" action="{{ route('admin.locations.toggle', $l) }}">@csrf @method('PATCH')<button class="btn-light" type="submit">{{ $l->is_active ? 'Deactivate' : 'Activate' }}</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('geocode-btn').addEventListener('click', function () {
    const q = document.getElementById('loc-name').value.trim();
    const msg = document.getElementById('geocode-msg');
    if (q.length < 3) { msg.textContent = 'Type at least 3 characters in the name first.'; return; }
    msg.textContent = 'Searching…';
    fetch('{{ route('geocode') }}?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then((r) => { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
        .then((d) => {
            if (!d.available) { msg.textContent = 'Geocoding unavailable: ' + d.message; return; }
            if (!d.results.length) { msg.textContent = d.message || 'No places found.'; return; }
            document.getElementById('loc-lat').value = d.results[0].latitude;
            document.getElementById('loc-lng').value = d.results[0].longitude;
            msg.textContent = 'Filled from: ' + d.results[0].name;
        })
        .catch(() => { msg.textContent = 'Look-up failed. Enter coordinates manually.'; });
});
</script>
@endpush
