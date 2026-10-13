@extends('layouts.public')

@section('metadata')
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="referrer" content="no-referrer">
<meta name="robots" content="noindex, nofollow">
<base href="{{ url('/') }}/">
<title>@yield('title') | FitFusion</title>
<link rel="stylesheet" href="{{ asset('assets/auth.css') }}">
@endsection

@section('content')
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>
<main class="auth-shell">
    <section class="auth-card" aria-labelledby="auth-title">
        <a href="{{ route('home') }}" class="auth-brand"><i class="bi bi-heart-pulse-fill" aria-hidden="true"></i> FitFusion</a>
        <h1 id="auth-title">@yield('title')</h1>
        <p class="auth-description">@yield('description')</p>
        @if(session('status'))
            <div class="auth-status" role="status">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="auth-errors" role="alert">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('form')
    </section>
</main>
<div id="my-footer" role="contentinfo" aria-label="Site footer"></div>
<button class="back-to-top" id="backToTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>
@endsection
