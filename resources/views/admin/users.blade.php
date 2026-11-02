@extends('layouts.app')
@section('title', 'Users')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Users</h1>
<form method="POST" action="{{ route('admin.users.store') }}" class="card mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
    @csrf
    <div><label class="label">Name *</label><input class="input" name="name" required value="{{ old('name') }}"></div>
    <div><label class="label">Email *</label><input class="input" type="email" name="email" required value="{{ old('email') }}"></div>
    <div><label class="label">Password * (min 8)</label><input class="input" type="password" name="password" required></div>
    <div><label class="label">Role *</label>
        <select class="input" name="role">@foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach</select></div>
    <div class="flex items-end"><button class="btn" type="submit">Create user</button></div>
</form>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50"><tr><th class="th">Name</th><th class="th">Email</th><th class="th">Role</th><th class="th">Change role</th></tr></thead>
        <tbody class="divide-y divide-ink-100">
        @foreach($users as $u)
            <tr>
                <td class="td font-medium">{{ $u->name }}</td><td class="td">{{ $u->email }}</td><td class="td">{{ $u->role }}</td>
                <td class="td">
                    @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.role', $u) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <select class="input w-auto" name="role">@foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected($u->role === $r)>{{ $r }}</option>@endforeach</select>
                            <button class="btn-light" type="submit">Save</button>
                        </form>
                    @else <span class="text-xs text-ink-400">(you)</span> @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
