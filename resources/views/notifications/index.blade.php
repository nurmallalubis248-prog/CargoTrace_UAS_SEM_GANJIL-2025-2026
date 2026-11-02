@extends('layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="mb-4 flex items-center justify-between">
    <h1 class="text-2xl font-extrabold tracking-tight text-matcha-900">Notifications</h1>
    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn-light" type="submit">Mark all as read</button></form>
</div>
<div class="card divide-y divide-ink-100 p-0">
    @forelse($notifications as $n)
        <div class="flex items-start gap-3 px-4 py-3 {{ $n->read_at ? '' : 'bg-matcha-50' }}">
            <span class="mt-1.5 h-2 w-2 rounded-full {{ $n->read_at ? 'bg-ink-300' : 'bg-matcha-600' }}"></span>
            <div>
                <p class="text-sm">{{ $n->message }}</p>
                <p class="text-xs text-ink-500">{{ $n->created_at->format('Y-m-d H:i') }} UTC
                    @if($n->shipment) &middot; <a class="text-matcha-700 hover:underline" href="{{ route('shipments.show', $n->shipment) }}">{{ $n->shipment->shipment_number }}</a>@endif</p>
            </div>
        </div>
    @empty
        <p class="px-4 py-8 text-center text-sm text-ink-500">No notifications yet.</p>
    @endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
