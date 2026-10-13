@extends('layouts.dashboard')
@section('title','Audit log')
@section('description','Recorded account and gym-management activity.')
@section('content')
<div class="dash-card"><form class="dash-search"><input name="q" value="{{ request('q') }}" placeholder="Search action" aria-label="Search audit actions"><button class="dash-button secondary">Search</button></form><div class="dash-table-wrap"><table class="dash-table"><thead><tr><th>When</th><th>Account</th><th>Action</th><th>Record</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ \App\Support\GymClock::display($log->created_at,'d M Y H:i') }}</td><td>{{ $log->user?->name ?? 'System / archived account' }}</td><td>{{ $log->action }}</td><td>{{ $log->entity_type }} {{ $log->entity_id }}</td></tr>@empty<tr><td colspan="4" class="dash-empty">No matching activity.</td></tr>@endforelse</tbody></table></div><div class="dash-pagination">{{ $logs->links() }}</div></div>
@endsection
