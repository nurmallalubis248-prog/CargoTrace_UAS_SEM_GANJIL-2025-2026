@extends('layouts.app')
@section('title', 'Edit '.$shipment->shipment_number)
@section('content')
<h1 class="mb-1 text-2xl font-bold">Edit {{ $shipment->shipment_number }}</h1>
<p class="mb-4 text-sm text-ink-500">Master data can only be edited while the shipment is CREATED or BOOKED. Status changes happen through tracking events.</p>
<form method="POST" action="{{ route('shipments.update', $shipment) }}" class="card">
    @csrf
    @method('PUT')
    @include('shipments._form')
    <div class="mt-6 flex gap-2">
        <button class="btn" type="submit">Save changes</button>
        <a class="btn-light" href="{{ route('shipments.show', $shipment) }}">Cancel</a>
    </div>
</form>
@endsection
