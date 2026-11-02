@extends('layouts.app')
@section('title', 'Audit log')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Audit log</h1>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y divide-ink-200">
        <thead class="bg-ink-50"><tr><th class="th">When (UTC)</th><th class="th">Actor</th><th class="th">Action</th><th class="th">Entity</th><th class="th">Details</th></tr></thead>
        <tbody class="divide-y divide-ink-100">
        @forelse($logs as $log)
            <tr>
                <td class="td whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                <td class="td">{{ $log->actor?->name ?? 'system' }}</td>
                <td class="td font-medium">{{ $log->action }}</td>
                <td class="td">{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                <td class="td max-w-md truncate text-xs text-ink-600">{{ $log->changes ? json_encode($log->changes) : '' }}</td>
            </tr>
        @empty
            <tr><td class="td py-6 text-center text-ink-500" colspan="5">No audit entries.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
