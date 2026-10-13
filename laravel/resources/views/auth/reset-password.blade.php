@extends('layouts.auth')
@section('title', 'Choose a new password')
@section('description', 'Set a secure new password for your FitFusion account.')
@section('form')
<form method="POST" action="{{ route('password.update') }}" class="auth-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label for="email">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required maxlength="254" autocomplete="username">
    <label for="password">New password</label>
    <input id="password" type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-help" autofocus>
    <p id="password-help" class="auth-help">Use 12 or more characters, with uppercase, lowercase and a number.</p>
    <label for="password_confirmation">Confirm new password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password">
    <button type="submit" class="btn-primary-custom">Reset password <i class="bi bi-lock" aria-hidden="true"></i></button>
</form>
<p class="auth-links"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
