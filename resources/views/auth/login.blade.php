@extends('layouts.guest')
@section('title', 'Log in')
@section('bare', '1')
{{-- Form login ada di popup "Masuk" (partials/auth-pill); di layar lebar halaman ini hanya gambar hero. --}}
@section('content')
@include('auth._landing')
@endsection
