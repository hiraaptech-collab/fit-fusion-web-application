@extends('layouts.dashboard')
@section('title','Edit '.($accountType==='managers'?'Manager':'Client'))
@section('content')
<div class="dash-card">@include('gym.partials.account-fields',['action'=>route('gym.account.update',[$accountType,$user->id])])</div>
@endsection
