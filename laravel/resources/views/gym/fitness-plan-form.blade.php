@extends('layouts.dashboard')
@section('title',($plan->exists?'Edit ':'Create ').($planType==='workouts'?'workout':'diet').' plan')
@section('content')
<div class="dash-card"><form method="POST" action="{{ $plan->exists?route('gym.fitness-plan.update',[$planType,$plan->id]):route('gym.fitness-plan.store',$planType) }}" class="dash-form">@csrf @if($plan->exists)@method('PUT')@endif
@include('gym.partials.field',['name'=>'title','label'=>'Plan title','value'=>$plan->title,'required'=>true])
@include('gym.partials.field',['name'=>'is_active','label'=>'Plan availability','type'=>'checkbox','value'=>$plan->exists?$plan->is_active:true])
@if($planType==='workouts')
@include('gym.partials.field',['name'=>'description','label'=>'Description','type'=>'textarea','value'=>$plan->description])
<div class="dash-field full"><label for="planExercises">Exercises (Ctrl/Command-click to select multiple)</label><select name="exercises[]" id="planExercises" multiple required>@foreach($exercises as $exercise)<option value="{{ $exercise->id }}" @selected(in_array($exercise->id,old('exercises',$plan->exists?$plan->planExercises->pluck('exercise_id')->all():[])))>{{ $exercise->category?->title }} · {{ $exercise->name }} · {{ $exercise->sets }} / {{ $exercise->reps }}</option>@endforeach</select></div>
@else
@foreach(['breakfast','lunch','dinner','snacks'] as $meal)@include('gym.partials.field',['name'=>$meal,'label'=>ucfirst($meal),'type'=>'textarea','value'=>$plan->$meal,'required'=>true])@endforeach
@include('gym.partials.field',['name'=>'calories','label'=>'Calories','value'=>$plan->calories])
@include('gym.partials.field',['name'=>'image','label'=>'Existing food image','type'=>'select','value'=>$plan->image,'options'=>$images->mapWithKeys(fn($image)=>[$image=>basename(parse_url($image,PHP_URL_PATH))])])
@foreach(['benefits','tips'] as $field)@include('gym.partials.field',['name'=>$field,'label'=>ucfirst($field).' (one per line)','type'=>'textarea','value'=>implode("\n",$plan->$field??[])])@endforeach
@include('gym.partials.field',['name'=>'notes','label'=>'Notes','type'=>'textarea','value'=>$plan->notes])
@endif
<div class="full dash-actions"><button class="dash-button">Save plan</button><a class="dash-button secondary" href="{{ route('gym.fitness-plans',$planType) }}">Cancel</a></div></form></div>
@endsection
