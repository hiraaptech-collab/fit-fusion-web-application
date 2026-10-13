@extends('layouts.dashboard')
@section('title', ucfirst(auth()->user()->role).' overview')
@section('description','A live view of your gym and the features you can manage.')
@section('content')
@if(empty($cards) && empty($finance))<div class="dash-card dash-empty">Your Owner has not assigned management permissions yet. Contact the Owner for access.</div>@endif
<div class="dash-grid">@foreach($cards as $label=>$count)<article class="dash-card dash-stat"><i class="bi bi-activity"></i><p>{{ $label }}</p><strong>{{ $count }}</strong></article>@endforeach</div>
@foreach($finance as $currency=>$totals)<h2 class="mt-4">Financial overview &middot; {{ $currency }}</h2><div class="dash-grid">@foreach($totals as $label=>$value)<article class="dash-card dash-stat"><p>{{ ucfirst($label) }}</p><strong>{{ number_format($value,2) }}</strong></article>@endforeach</div>@endforeach
@can('reports.view')<div class="dash-card"><h2>Understand your gym's activity</h2><p>Explore memberships, Client registrations and your approved financial reports by month.</p><a class="dash-button" href="{{ route('gym.reports') }}">Open interactive reports <i class="bi bi-arrow-right ms-2"></i></a></div>@endcan
<div class="dash-card"><h2>Your workspace</h2><p>Use the sidebar to manage your approved features. Statistics reflect stored gym records.</p><div class="dash-actions"><a class="dash-button secondary" href="{{ route('gym.notifications') }}">Notifications</a><a class="dash-button secondary" href="{{ route('home') }}">Visit FitFusion</a></div></div>
@can('feedback.manage')<div class="dash-card"><h2>Listen to your Clients</h2><p>Review new feedback and manage what appears on the public page.</p><a class="dash-button" href="{{ route('gym.reviews') }}">Open Feedback Management</a></div>@endcan
@endsection

