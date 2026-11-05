@extends('layouts.app')
@section('title', 'Warehouses')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Warehouses</h1>
<form method="POST" action="{{ route('admin.warehouses.store') }}" class="card mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
    @csrf
    <div><label class="label">Name *</label><input class="input" name="name" required value="{{ old('name') }}"></div>
    <div><label class="label">Code *</label><input class="input" name="code" required maxlength="12" value="{{ old('code') }}"></div>
    <div><label class="label">Linked location</label>
        <select class="input" name="location_id"><option value="">None</option>@foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach</select></div>
    <div><label class="label">Capacity (kg) *</label><input class="input" type="number" min="1" name="capacity_kg" required value="{{ old('capacity_kg') }}"></div>
    <div class="flex items-end"><button class="btn" type="submit">Add warehouse</button></div>
</form>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50"><tr><th class="th">Name</th><th class="th">Code</th><th class="th">Location</th><th class="th">Capacity</th><th class="th">Load</th><th class="th">Utilization</th><th class="th">Active</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-ink-100">
        @foreach($warehouses as $w)
            @php $load = $w->loadKg(); $pct = min(100, round($load / max(1, $w->capacity_kg) * 100)); @endphp
            <tr>
                <td class="td font-medium">{{ $w->name }}</td><td class="td">{{ $w->code }}</td><td class="td">{{ $w->location?->name ?? '-' }}</td>
                <td class="td">{{ number_format($w->capacity_kg, 0, ',', '.') }} kg</td><td class="td">{{ number_format($load, 0, ',', '.') }} kg</td>
                <td class="td"><div class="h-2 w-32 rounded-full bg-ink-100"><div class="grad-brand h-2 rounded-full" style="width: {{ $pct }}%"></div></div><span class="text-xs text-ink-500">{{ $pct }}%</span></td>
                <td class="td">{{ $w->is_active ? 'Yes' : 'No' }}</td>
                <td class="td"><form method="POST" action="{{ route('admin.warehouses.toggle', $w) }}">@csrf @method('PATCH')<button class="btn-light" type="submit">{{ $w->is_active ? 'Deactivate' : 'Activate' }}</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
