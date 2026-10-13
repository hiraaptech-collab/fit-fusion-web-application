@extends('layouts.dashboard')
@section('title','Edit staff record')
@section('content')<div class="dash-card">@include('gym.partials.staff-fields',['action'=>route('gym.staff.update',[$staffType,$staff->id])])</div>@endsection
