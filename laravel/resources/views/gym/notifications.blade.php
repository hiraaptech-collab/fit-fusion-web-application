@extends('layouts.dashboard')
@section('title','Notifications')
@section('content')
<div class="dash-card">@forelse($notifications as $notification)<article class="dash-message"><h2>{{ $notification->data['title'] ?? 'Gym notification' }}</h2><p>{{ $notification->data['body'] ?? '' }}</p><small>{{ \App\Support\GymClock::display($notification->created_at,'d M Y H:i') }}</small>@unless($notification->read_at)<form method="POST" action="{{ route('gym.notification.read',$notification->id) }}">@csrf @method('PUT')<button class="dash-button secondary">Mark read</button></form>@endunless</article>@empty<p class="dash-empty">You're all caught up.</p>@endforelse<div class="dash-pagination">{{ $notifications->links() }}</div></div>
@if(auth()->user()->role==='owner')<div class="dash-card"><h2>Send gym notification</h2><form method="POST" action="{{ route('gym.notification.store') }}" class="dash-form">@csrf
@include('gym.partials.field',['name'=>'title','label'=>'Title','required'=>true])
@include('gym.partials.field',['name'=>'audience','label'=>'Audience','type'=>'select','options'=>['client'=>'Clients','manager'=>'Managers','all'=>'All active accounts'],'required'=>true])
@include('gym.partials.field',['name'=>'recipient_id','label'=>'Specific account (optional)','type'=>'select','options'=>$recipients->mapWithKeys(fn($person)=>[$person->id=>$person->name.' ('.$person->role.')'])])
@include('gym.partials.field',['name'=>'body','label'=>'Notification body','type'=>'textarea','required'=>true])<div class="full"><button class="dash-button">Send notification</button></div></form></div>@endif
@endsection


