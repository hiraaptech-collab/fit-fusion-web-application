@extends('layouts.dashboard')
@section('title','My profile')
@section('description','Keep your personal and fitness details up to date.')
@section('content')
<div class="dash-card"><p>Account email: {{ $user->email }}</p><form method="POST" action="{{ route('client.profile.update') }}" class="dash-form">@csrf @method('PUT')
@include('gym.partials.field',['name'=>'name','label'=>'Full name','value'=>$user->name,'required'=>true])
@foreach(['phone'=>'Phone number','date_of_birth'=>'Date of birth','gender'=>'Gender','address'=>'Address','emergency_contact_name'=>'Emergency contact name','emergency_contact_phone'=>'Emergency contact phone','fitness_goal'=>'Fitness goal'] as $field=>$label)
@include('gym.partials.field',['name'=>$field,'label'=>$label,'type'=>$field==='date_of_birth'?'date':(in_array($field,['address','fitness_goal'])?'textarea':'text'),'value'=>$field==='date_of_birth'?$profile?->date_of_birth?->toDateString():$profile?->$field])
@endforeach
@include('gym.partials.field',['name'=>'fitness_consent','label'=>'Fitness data consent','type'=>'checkbox','value'=>(bool)$profile?->fitness_data_consent_at,'checkLabel'=>'Allow my fitness data to be stored for my personal portal'])
<p class="full">Turning consent off stops optional collection. You can remove your progress entries and AI conversations from their pages.</p><div class="full"><button class="dash-button">Save profile</button></div></form></div>
@endsection
