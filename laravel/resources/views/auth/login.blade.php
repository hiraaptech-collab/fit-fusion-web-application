@extends('layouts.auth')
@section('title', 'Welcome back')
@section('description', 'Sign in to your FitFusion gym account.')
@section('form')
<form method="POST" action="{{ route('login.store') }}" class="auth-form">
    @csrf
    <label for="email">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="username" autofocus>
    <label for="password">Password</label>
    <input id="password" type="password" name="password" required maxlength="72" autocomplete="current-password">
    <div class="auth-options">
        <label class="auth-checkbox"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </div>
    <button type="submit" class="btn-primary-custom">Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>
<p class="auth-links">New to FitFusion? <a href="{{ route('register') }}">Create a client account</a></p>
@endsection
