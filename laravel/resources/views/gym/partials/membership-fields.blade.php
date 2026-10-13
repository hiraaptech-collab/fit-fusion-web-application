<form method="POST" action="{{ $previous?route('gym.membership.renew',$previous):route('gym.membership.store') }}" class="dash-form">@csrf
@if($previous)<input type="hidden" name="user_id" value="{{ $previous->user_id }}"><p class="full">Renew {{ $previous->user?->name }} with a new membership. The old membership stays in history.</p>@else @include('gym.partials.field',['name'=>'user_id','label'=>'Client','type'=>'select','options'=>$clients->pluck('name','id'),'required'=>true])@endif
@include('gym.partials.field',['name'=>'membership_plan_id','label'=>'Membership plan','type'=>'select','options'=>$plans->mapWithKeys(fn($p)=>[$p->id=>$p->name.' · '.$p->currency.' '.$p->price]),'required'=>true])
@include('gym.partials.field',['name'=>'starts_on','label'=>'Starts on','type'=>'date','value'=>$previous?$previous->ends_on->copy()->addDay()->toDateString():\App\Support\GymClock::today()->toDateString(),'required'=>true])
@include('gym.partials.field',['name'=>'notes','label'=>'Notes','type'=>'textarea'])
<div class="full"><button class="dash-button">{{ $previous?'Renew membership':'Create membership' }}</button></div></form>
