<form method="POST" action="{{ $plan->exists?route('gym.membership-plan.update',$plan->id):route('gym.membership-plan.store') }}" class="dash-form">@csrf @if($plan->exists)@method('PUT')@endif
@include('gym.partials.field',['name'=>'name','label'=>'Plan name','value'=>$plan->name,'required'=>true])
@include('gym.partials.field',['name'=>'duration_days','label'=>'Duration in days','type'=>'number','value'=>$plan->duration_days,'required'=>true])
@include('gym.partials.field',['name'=>'price','label'=>'Price','type'=>'number','step'=>'0.01','value'=>$plan->price,'required'=>true])
@include('gym.partials.field',['name'=>'currency','label'=>'Currency','value'=>$plan->currency??\App\Models\GymSetting::find(1)->currency,'required'=>true])
@include('gym.partials.field',['name'=>'description','label'=>'Description','type'=>'textarea','value'=>$plan->description])
@include('gym.partials.field',['name'=>'is_active','label'=>'Plan availability','type'=>'checkbox','value'=>$plan->exists?$plan->is_active:true])
<div class="full"><button class="dash-button">Save plan</button></div></form>
