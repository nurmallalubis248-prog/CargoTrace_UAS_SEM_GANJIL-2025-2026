@extends('layouts.app')
@section('title', 'New shipment')
@section('content')
<h1 class="mb-4 text-2xl font-bold">New shipment</h1>
<form method="POST" action="{{ route('shipments.store') }}" class="card">
    @csrf
    @include('shipments._form')
    <div class="mt-6 flex gap-2">
        <button class="btn" type="submit">Create shipment</button>
        <a class="btn-light" href="{{ route('shipments.index') }}">Cancel</a>
    </div>
</form>
@endsection
