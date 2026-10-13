@extends('layouts.dashboard')
@section('title',ucfirst($operation))
@section('description','Organize staff availability and retain attendance history.')
@section('content')
<p>Schedule and attendance times use {{ \App\Support\GymClock::timezone() }}.</p>
<div class="dash-card"><div class="dash-table-wrap"><table class="dash-table"><thead><tr><th>ID / staff</th><th>{{ $operation==='schedules'?'Schedule':'Date' }}</th><th>{{ $operation==='schedules'?'Notes':'Attendance' }}</th><th>Actions / details</th></tr></thead><tbody>@forelse($records as $record)<tr><td>#{{ $record->id }} {{ $record->trainer?->name ?? $record->worker?->name ?? 'Archived staff' }}</td><td>{{ $operation==='schedules'?\App\Support\GymClock::display($record->starts_at,'d M Y H:i'):$record->attendance_date->format('d M Y') }}@if($operation==='schedules')<small>to {{ \App\Support\GymClock::display($record->ends_at,'d M Y H:i') }}</small>@endif</td><td>{{ $operation==='schedules'?$record->notes:ucfirst($record->status) }}</td><td>@if($operation==='schedules')<details><summary>Edit schedule</summary>@include('gym.partials.schedule-fields',['record'=>$record])</details><form method="POST" action="{{ route('gym.schedule.delete',$record->id) }}" data-confirm="Remove this schedule? Schedules with attendance history cannot be removed.">@csrf @method('DELETE')<button class="dash-button danger">Remove</button></form>@else<small>In {{ \App\Support\GymClock::display($record->checked_in_at,'H:i') ?? '—' }} / Out {{ \App\Support\GymClock::display($record->checked_out_at,'H:i') ?? '—' }}</small>{{ $record->notes }}@endif</td></tr>@empty<tr><td colspan="4" class="dash-empty">No {{ $operation }} recorded yet.</td></tr>@endforelse</tbody></table></div><div class="dash-pagination">{{ $records->links() }}</div></div>
<div class="dash-card"><h2>{{ $operation==='schedules'?'Add schedule':'Record / correct attendance' }}</h2>@if($operation==='schedules')@include('gym.partials.schedule-fields',['record'=>new \App\Models\StaffSchedule()])@else
<form method="POST" action="{{ route('gym.attendance.store') }}" class="dash-form">@csrf
@include('gym.partials.staff-select')
@include('gym.partials.field',['name'=>'attendance_date','label'=>'Attendance date','type'=>'date','value'=>\App\Support\GymClock::today()->toDateString(),'required'=>true])
@include('gym.partials.field',['name'=>'status','label'=>'Status','type'=>'select','options'=>['present'=>'Present','absent'=>'Absent','late'=>'Late','leave'=>'Leave'],'required'=>true])
@include('gym.partials.field',['name'=>'staff_schedule_id','label'=>'Matching schedule ID (optional)','type'=>'number'])
@include('gym.partials.field',['name'=>'checked_in_at','label'=>'Checked in (optional)','type'=>'datetime-local'])
@include('gym.partials.field',['name'=>'checked_out_at','label'=>'Checked out (optional)','type'=>'datetime-local'])
@include('gym.partials.field',['name'=>'notes','label'=>'Notes','type'=>'textarea'])
<p class="full">Saving the same staff member and date corrects that existing attendance entry.</p><div class="full"><button class="dash-button">Save attendance</button></div></form>@endif</div>
@endsection

