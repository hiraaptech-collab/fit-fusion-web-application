<form method="POST" action="{{ route('gym.expense.store') }}" class="dash-form">@csrf
@include('gym.partials.field',['name'=>'description','label'=>'Description','required'=>true])
@include('gym.partials.field',['name'=>'category','label'=>'Category','required'=>true])
@include('gym.partials.field',['name'=>'amount','label'=>'Amount','type'=>'number','step'=>'0.01','required'=>true])
@include('gym.partials.field',['name'=>'currency','label'=>'Currency','value'=>$currency,'required'=>true])
@include('gym.partials.field',['name'=>'incurred_on','label'=>'Incurred on','type'=>'date','value'=>\App\Support\GymClock::today()->toDateString(),'required'=>true])
@include('gym.partials.field',['name'=>'reference','label'=>'External reference (optional)'])
@include('gym.partials.field',['name'=>'notes','label'=>'Notes','type'=>'textarea'])
<div class="full"><button class="dash-button">Record expense</button></div></form>
