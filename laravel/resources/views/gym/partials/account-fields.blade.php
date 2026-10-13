<form method="POST" action="{{ $action }}" class="dash-form">@csrf @if($user->exists)@method('PUT')@endif
@include('gym.partials.field',['name'=>'name','label'=>'Full name','value'=>$user->name,'required'=>true])
@include('gym.partials.field',['name'=>'email','label'=>'Email address','type'=>'email','value'=>$user->email,'required'=>true])
@include('gym.partials.field',['name'=>'password','label'=>$user->exists?'New password (leave blank to keep)':'Password (12+ characters, mixed case and number)','type'=>'password','required'=>!$user->exists])
@include('gym.partials.field',['name'=>'password_confirmation','label'=>'Confirm password','type'=>'password','required'=>!$user->exists])
@include('gym.partials.field',['name'=>'is_active','label'=>'Account access','type'=>'checkbox','value'=>$user->exists?$user->is_active:true,'checkLabel'=>'Active account'])
@if($accountType==='clients')
@foreach(['phone'=>'Phone','gender'=>'Gender','address'=>'Address','emergency_contact_name'=>'Emergency contact','emergency_contact_phone'=>'Emergency phone','fitness_goal'=>'Fitness goal'] as $profileField=>$profileLabel)
@include('gym.partials.field',['name'=>$profileField,'label'=>$profileLabel,'value'=>$user->clientProfile?->$profileField,'type'=>in_array($profileField,['address','fitness_goal'])?'textarea':'text'])
@endforeach
@include('gym.partials.field',['name'=>'date_of_birth','label'=>'Date of birth','type'=>'date','value'=>$user->clientProfile?->date_of_birth?->toDateString()])
@endif
@if($accountType==='managers')<fieldset class="full"><legend class="fs-6">Owner-approved permissions</legend><input type="hidden" name="permissions_present" value="1"><div class="dash-permissions">@foreach(config('gym.manager_permissions') as $permission=>$label)<label class="dash-check"><input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission,old('permissions',$user->exists?$user->managerPermissions->pluck('permission')->all():[])))> {{ $label }}</label>@endforeach</div></fieldset>@endif
<div class="full dash-actions"><button class="dash-button" type="submit">Save {{ $accountType==='managers'?'Manager':'Client' }}</button>@if($user->exists)<a class="dash-button secondary" href="{{ route('gym.accounts',$accountType) }}">Cancel</a>@endif</div>
</form>

