<form method="POST" action="{{ $record->exists?route('gym.schedule.update',$record->id):route('gym.schedule.store') }}" class="dash-form">@csrf @if($record->exists)@method('PUT')@endif
@include('gym.partials.staff-select')
@include('gym.partials.field',['name'=>'starts_at','label'=>'Starts at','type'=>'datetime-local','value'=>\App\Support\GymClock::display($record->starts_at,'Y-m-d\TH:i'),'required'=>true])
@include('gym.partials.field',['name'=>'ends_at','label'=>'Ends at','type'=>'datetime-local','value'=>\App\Support\GymClock::display($record->ends_at,'Y-m-d\TH:i'),'required'=>true])
@include('gym.partials.field',['name'=>'notes','label'=>'Notes','type'=>'textarea','value'=>$record->notes])
<div class="full"><button class="dash-button">Save schedule</button></div></form>
