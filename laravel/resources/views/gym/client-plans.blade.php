@extends('layouts.dashboard')
@section('title','My fitness plans')
@section('description','Workout and diet plans assigned by your gym.')
@section('content')
@forelse($assignments as $assignment)<article class="dash-card"><h2>{{ $assignment->workoutPlan?->title ?? $assignment->dietPlan?->title }}</h2><p>{{ $assignment->cancelled_at ? 'Ended · ' : '' }}{{ $assignment->starts_on->format('d M Y') }} to {{ $assignment->ends_on?->format('d M Y') ?? 'ongoing' }}</p><p>{{ $assignment->notes }}</p>
@if($assignment->workoutPlan)<p>{{ $assignment->workoutPlan->description }}</p><div class="dash-table-wrap"><table class="dash-table"><thead><tr><th>Exercise</th><th>Sets</th><th>Reps</th><th>Tips</th></tr></thead><tbody>@foreach($assignment->workoutPlan->planExercises->sortBy('sort_order') as $item)<tr><td>{{ $item->exercise?->name }}</td><td>{{ $item->sets }}</td><td>{{ $item->reps }}</td><td>{{ $item->notes ?? $item->exercise?->tips }}</td></tr>@endforeach</tbody></table></div>@else
@foreach(['breakfast','lunch','dinner','snacks'] as $meal)<h3 class="mt-3">{{ ucfirst($meal) }}</h3><p>{{ $assignment->dietPlan?->$meal }}</p>@endforeach<p>{{ $assignment->dietPlan?->notes }}</p>
@endif</article>@empty<div class="dash-card dash-empty">No fitness plans have been assigned yet.</div>@endforelse<div class="dash-pagination">{{ $assignments->links() }}</div>
@endsection

