<form method="POST" action="{{ route('gym.payment.store') }}" class="dash-form">@csrf
@include('gym.partials.field',['name'=>'user_id','label'=>'Client','type'=>'select','options'=>$clients->pluck('name','id'),'required'=>true])
@include('gym.partials.field',['name'=>'membership_id','label'=>'Membership (optional)','type'=>'select','options'=>$memberships->mapWithKeys(fn($m)=>[$m->id=>'#'.$m->id.' '.$m->user?->name.' · '.$m->plan_name.' '.$m->currency])])
@include('gym.partials.field',['name'=>'amount','label'=>'Amount','type'=>'number','step'=>'0.01','required'=>true])
@include('gym.partials.field',['name'=>'currency','label'=>'Currency','value'=>$currency,'required'=>true])
@include('gym.partials.field',['name'=>'method','label'=>'Payment method','type'=>'select','options'=>['cash'=>'Cash','bank_transfer'=>'Bank transfer','card'=>'Card','other'=>'Other'],'required'=>true])
@include('gym.partials.field',['name'=>'status','label'=>'Payment status','type'=>'select','options'=>['paid'=>'Paid','pending'=>'Pending','failed'=>'Failed'],'required'=>true])
@include('gym.partials.field',['name'=>'paid_at','label'=>'Paid at (optional, '.\App\Support\GymClock::timezone().')','type'=>'datetime-local'])
@include('gym.partials.field',['name'=>'reference','label'=>'External reference (optional)'])
@include('gym.partials.field',['name'=>'notes','label'=>'Notes','type'=>'textarea'])
<div class="full"><button class="dash-button">Record payment</button></div></form>

