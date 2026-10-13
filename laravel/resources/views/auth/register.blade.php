@extends('layouts.auth')
@section('title', 'Join FitFusion')
@section('description', 'Create your client account and begin your fitness journey.')
@section('form')
<form method="POST" action="{{ route('register.store') }}" class="auth-form">
    @csrf
    <label for="name">Full name</label>
    <input id="name" type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" autofocus>
    <label for="email">Email address</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required maxlength="254" autocomplete="email">
    <label for="password">Password</label>
    <input id="password" type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-help">
    <p id="password-help" class="auth-help">Use 12 or more characters, with uppercase, lowercase and a number.</p>
    <label for="password_confirmation">Confirm password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" maxlength="72" autocomplete="new-password">
    <button type="submit" class="btn-primary-custom">Create account <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
</form>
<p class="auth-links">Already a member? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
