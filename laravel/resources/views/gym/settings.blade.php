@extends('layouts.dashboard')
@section('title','Gym settings')
@section('description','Owner-only settings for this single gym installation.')
@section('content')
<div class="dash-card"><form method="POST" action="{{ route('gym.settings.update') }}" class="dash-form">@csrf @method('PUT')
@foreach(['name'=>'Gym name','email'=>'Gym email','phone'=>'Gym phone / WhatsApp (include country code)','address'=>'Address','timezone'=>'Time zone','currency'=>'Default currency'] as $field=>$label)
@include('gym.partials.field',['name'=>$field,'label'=>$label,'value'=>$settings->$field,'type'=>$field==='email'?'email':($field==='address'?'textarea':'text'),'required'=>in_array($field,['name','timezone','currency'])])
@endforeach
@include('gym.partials.field',['name'=>'opening_hours','label'=>'Opening hours (JSON, e.g. {"Monday":"06:00–22:00"})','type'=>'textarea','value'=>$settings->opening_hours?json_encode($settings->opening_hours,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE):''])
<p class="full">Default currency changes apply to new entries. Existing financial and membership currencies remain unchanged.</p><div class="full"><button class="dash-button">Save gym settings</button></div></form></div>
@endsection
