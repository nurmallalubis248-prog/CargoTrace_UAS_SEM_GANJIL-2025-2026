@extends('layouts.app')
@section('title', 'Carriers')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Carriers</h1>
<form method="POST" action="{{ route('admin.carriers.store') }}" class="card mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
    @csrf
    <div><label class="label">Name *</label><input class="input" name="name" required value="{{ old('name') }}"></div>
    <div><label class="label">Code * (max 10)</label><input class="input" name="code" required maxlength="10" value="{{ old('code') }}"></div>
    <div><label class="label">Contact email</label><input class="input" type="email" name="contact_email" value="{{ old('contact_email') }}"></div>
    <div class="flex items-end"><button class="btn" type="submit">Add carrier</button></div>
</form>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50"><tr><th class="th">Name</th><th class="th">Code</th><th class="th">Email</th><th class="th">Shipments</th><th class="th">Active</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-ink-100">
        @foreach($carriers as $c)
            <tr>
                <td class="td font-medium">{{ $c->name }}</td><td class="td">{{ $c->code }}</td>
                <td class="td">{{ $c->contact_email ?? '-' }}</td><td class="td">{{ $c->shipments_count }}</td>
                <td class="td">{{ $c->is_active ? 'Yes' : 'No' }}</td>
                <td class="td"><form method="POST" action="{{ route('admin.carriers.toggle', $c) }}">@csrf @method('PATCH')<button class="btn-light" type="submit">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
