@extends('layouts.auth')
@section('title', 'You are signed in')
@section('description', 'Your FitFusion account is ready.')
@section('form')
<dl class="auth-account">
    <dt>Name</dt><dd>{{ auth()->user()->name }}</dd>
    <dt>Email</dt><dd>{{ auth()->user()->email }}</dd>
    <dt>Account role</dt><dd>{{ ucfirst(auth()->user()->role) }}</dd>
</dl>
<div class="auth-feature-links">
    <a href="{{ route('workouts') }}">Workouts</a>
    <a href="{{ route('dietplans') }}">Diet plans</a>
    <a href="{{ route('bmi') }}">BMI Calculator</a>
    <a href="{{ route('fusion-ai') }}">Fusion-AI</a>
</div>
<form method="POST" action="{{ route('logout') }}" class="auth-form">
    @csrf
    <button type="submit" class="btn-outline-custom">Sign out <i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
</form>
@endsection
