@extends('layouts.dashboard')
@section('title','Membership plans')
@section('description','Manage new offers while keeping existing membership prices unchanged.')
@section('content')
<div class="dash-card"><div class="dash-table-wrap"><table class="dash-table"><thead><tr><th>Plan</th><th>Term</th><th>Price</th><th>Status</th><th>Manage</th></tr></thead><tbody>@forelse($plans as $plan)<tr><td>{{ $plan->name }}</td><td>{{ $plan->duration_days }} days</td><td>{{ $plan->currency }} {{ $plan->price }}</td><td>{{ $plan->trashed()?'Archived':($plan->is_active?'Active':'Inactive') }}</td><td><details><summary>Edit / restore</summary>@include('gym.partials.membership-plan-fields')</details>@unless($plan->trashed())<form method="POST" action="{{ route('gym.membership-plan.archive',$plan) }}" data-confirm="Archive this plan? Existing memberships and payments will remain.">@csrf @method('DELETE')<button class="dash-button danger">Archive</button></form>@endunless</td></tr>@empty<tr><td colspan="5" class="dash-empty">No membership plans yet. Create your first offer below.</td></tr>@endforelse</tbody></table></div><div class="dash-pagination">{{ $plans->links() }}</div></div>
<div class="dash-card"><details open><summary>Create membership plan</summary>@include('gym.partials.membership-plan-fields',['plan'=>new \App\Models\MembershipPlan()])</details></div>
@endsection
