<!DOCTYPE html>
<html lang="en">
<head>
@yield('metadata')
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/navbar-auth.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Same-origin Laravel configuration for WhatsApp. -->
    <meta name="fitfusion-config-url" content="{{ url('/api/config') }}">
</head>
<body>
<!-- Laravel supplies the correct links; shared JavaScript places them in both menus. -->
<template id="navbar-auth-template">
    @auth
        <a href="{{ route('dashboard') }}" class="navbar-auth-link navbar-auth-primary">Dashboard</a>
        <form method="POST" action="{{ route('logout') }}" class="navbar-logout-form">
            @csrf
            <button type="submit" class="navbar-auth-link">Logout</button>
        </form>
    @else
        <a href="{{ route('login') }}" class="navbar-auth-link">Login</a>
        <a href="{{ route('register') }}" class="navbar-auth-link navbar-auth-primary">Register</a>
    @endauth
</template>
@yield('content')

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- AOS Animation -->
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="{{ asset('javascript/public-submissions.js') }}"></script>
<!-- Custom JS -->
<script src="{{ asset('javascript/script.js') }}"></script>
@yield('page-scripts')
</body>
</html>
