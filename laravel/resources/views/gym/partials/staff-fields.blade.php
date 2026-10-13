<form method="POST" action="{{ $action }}" class="dash-form">@csrf @if($staff->exists)@method('PUT')@endif
@foreach(['name'=>'Full name','email'=>'Email address','phone'=>'Phone number'] as $field=>$label)@include('gym.partials.field',['name'=>$field,'label'=>$label,'value'=>$staff->$field,'type'=>$field==='email'?'email':'text','required'=>$field==='name'])@endforeach
@include('gym.partials.field',['name'=>'hired_on','label'=>'Hired on','type'=>'date','value'=>$staff->hired_on?->format('Y-m-d')])
@if($staffType==='trainers')@include('gym.partials.field',['name'=>'specialties','label'=>'Specialties','type'=>'textarea','value'=>$staff->specialties])@include('gym.partials.field',['name'=>'qualifications','label'=>'Qualifications','type'=>'textarea','value'=>$staff->qualifications])@else @include('gym.partials.field',['name'=>'job_title','label'=>'Job title','value'=>$staff->job_title,'required'=>true])@endif
@include('gym.partials.field',['name'=>'is_active','label'=>'Staff status','type'=>'checkbox','value'=>$staff->exists?$staff->is_active:true,'checkLabel'=>'Active staff member'])
<div class="full"><button class="dash-button" type="submit">Save staff record</button></div></form>
