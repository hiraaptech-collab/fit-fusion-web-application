@extends('layouts.dashboard')
@section('title','My membership')
@section('description','Your membership terms, dates and renewal history.')
@section('content')
@forelse($memberships as $membership)<article class="dash-card"><h2>{{ $membership->plan_name }}</h2><p><span class="dash-badge">{{ ucfirst($membership->effective_status) }}</span></p><p>{{ $membership->starts_on->format('d M Y') }} — {{ $membership->ends_on->format('d M Y') }} ({{ $membership->duration_days }} days)</p><p>Agreed price {{ $membership->currency }} {{ $membership->agreed_price }}</p><p>Net paid {{ $membership->currency }} {{ number_format($membership->net_paid,2) }} &middot; Balance {{ number_format(max(0,$membership->agreed_price-$membership->net_paid),2) }}</p><p>{{ $membership->notes }}</p><a class="dash-button secondary" href="{{ route('gym.messages') }}">Ask about renewal</a></article>@empty<div class="dash-card dash-empty">You do not have a membership yet. Contact the gym to join.</div>@endforelse
<div class="dash-pagination">{{ $memberships->links() }}</div><a href="{{ route('public.memberships') }}" class="dash-button secondary">Available membership plans</a>
@endsection

