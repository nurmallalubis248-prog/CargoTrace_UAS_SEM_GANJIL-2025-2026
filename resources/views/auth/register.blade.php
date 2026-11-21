@extends('layouts.guest')
@section('title', 'Register')
@section('bare', '1')
{{-- Form daftar pelanggan ada di popup (partials/auth-pill), terbuka otomatis di halaman ini. --}}
@section('content')
@include('auth._landing')
@endsection
