@extends('layouts.app')
@php $asCustomer = auth()->user()?->isCustomer() ?? false; @endphp
@section('title', $asCustomer ? 'Request shipment' : 'New shipment')
@section('content')
<h1 class="mb-1 text-2xl font-bold">{{ $asCustomer ? 'Request shipment' : 'New shipment' }}</h1>
@if($asCustomer)
    <p class="mb-4 text-sm text-ink-500">Your request will be reviewed by an admin or operator, who will set the departure and arrival schedule. You will be notified once it is processed.</p>
@endif
<form method="POST" action="{{ route('shipments.store') }}" class="card">
    @csrf
    @include('shipments._form')
    <div class="mt-6 flex gap-2">
        <button class="btn" type="submit">{{ $asCustomer ? 'Submit request' : 'Create shipment' }}</button>
        <a class="btn-light" href="{{ route('shipments.index') }}">Cancel</a>
    </div>
</form>
@endsection
