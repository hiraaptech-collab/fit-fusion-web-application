@extends('layouts.dashboard')
@section('title',ucfirst($accountType))
@section('description','Create accounts, update details and control active access.')
@section('content')
<div class="dash-card"><h2>Accounts</h2><form class="dash-search" method="GET"><input name="q" value="{{ request('q') }}" placeholder="Search name or email" aria-label="Search accounts"><button class="dash-button secondary">Search</button></form><div class="dash-table-wrap"><table class="dash-table"><thead><tr><th>Name</th><th>Email</th><th>Access</th><th>Actions</th></tr></thead><tbody>@forelse($records as $record)<tr><td>{{ $record->name }}</td><td>{{ $record->email }}</td><td><span class="dash-badge">{{ $record->is_active?'Active':'Inactive' }}</span></td><td><a class="dash-button secondary" href="{{ route('gym.account.edit',[$accountType,$record->id]) }}">Edit {{ $accountType==='managers'?'& permissions':'' }}</a></td></tr>@empty<tr><td colspan="4" class="dash-empty">No matching accounts.</td></tr>@endforelse</tbody></table></div><div class="dash-pagination">{{ $records->links() }}</div></div>
<div class="dash-card"><details><summary>Create {{ $accountType==='managers'?'Manager':'Client' }} account</summary>@include('gym.partials.account-fields',['user'=>new \App\Models\User(),'action'=>route('gym.account.store',$accountType)])</details></div>
@endsection
