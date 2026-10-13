@extends('layouts.auth')
@section('title', 'Forgot password?')
@section('description', 'Enter your email address to request a password reset link.')
@section('form')
<form method="POST" action="{{ route('password.email') }}" class="auth-form">
    @csrf
    <label for="email">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email" autofocus>
    <button type="submit" class="btn-primary-custom">Send reset link <i class="bi bi-envelope" aria-hidden="true"></i></button>
</form>
<p class="auth-links"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
