<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleRequest;
use App\Http\Requests\AttendanceRequest;
use App\Models\{Trainer,Worker,StaffSchedule,StaffAttendance};
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
use Illuminate\Validation\ValidationException;
class StaffOperationsController extends Controller {
    public function index(Request $request,string $operation) {
        Gate::authorize('staff.'.$operation);
        $records = $operation === 'schedules' ? StaffSchedule::with(['trainer','worker'])->orderByDesc('starts_at')
        : StaffAttendance::with(['trainer','worker'])->orderByDesc('attendance_date');
        $records = $records->paginate(20);
        // Include archived staff when correcting historical attendance.
        $trainers = $operation === 'attendance'
            ? Trainer::withTrashed()->orderBy('name')->get()
            : Trainer::where('is_active', true)->orderBy('name')->get();
        $workers = $operation === 'attendance'
            ? Worker::withTrashed()->orderBy('name')->get()
            : Worker::where('is_active', true)->orderBy('name')->get();
        return view('gym.staff-operations',compact('records','operation','trainers','workers'));
    }
    public function schedule(ScheduleRequest $request, ?int $record = null) {
        DB::transaction(function() use($request,$record) {
            $model=$request->staff_type === 'trainer' ? Trainer::class : Worker::class;
            $staff=$model::where('is_active',true)->lockForUpdate()->findOrFail($request->staff_id);
            $column=$request->staff_type.'_id';
            $conflict=StaffSchedule::where($column,$staff->id)->where('starts_at','<',$request->ends_at)->where('ends_at','>',$request->starts_at)
            ->when($record,fn($q)=>$q->where('id','!=',$record))->exists();
            if($conflict) throw ValidationException::withMessages(['starts_at'=>'This staff member already has a schedule during that time.']);
            $schedule=$record ? StaffSchedule::findOrFail($record) : new StaffSchedule();
            if($record && $schedule->attendances()->exists()) throw ValidationException::withMessages(['starts_at'=>'This schedule has attendance history and cannot be changed.']);
            $schedule->fill(['trainer_id'=>null,'worker_id'=>null,$column=>$staff->id,
            ...$request->safe()->only(['starts_at','ends_at','notes'])]);
            if(!$schedule->exists) $schedule->created_by=auth()->id(); $schedule->save(); GymActivity::record('staff.schedule_saved',$schedule);
        }
        );
        return back()->with('status','Schedule saved.');
    }
    public function deleteSchedule(int $record) {
        Gate::authorize('staff.schedules');
        $schedule=StaffSchedule::findOrFail($record);
        if($schedule->attendances()->exists()) throw ValidationException::withMessages(['schedule'=>'Keep schedules that have attendance history.']);
        GymActivity::record('staff.schedule_removed',$schedule);
        $schedule->delete();
        return back()->with('status','Unreferenced schedule removed.');
    }
    public function attendance(AttendanceRequest $request) {
        DB::transaction(function() use($request) {
            $model=$request->staff_type==='trainer' ? Trainer::class : Worker::class;
            $staff=$model::withTrashed()->lockForUpdate()->findOrFail($request->staff_id); $column=$request->staff_type.'_id';
            if ($request->filled('checked_in_at') && \Illuminate\Support\Carbon::parse($request->checked_in_at)->timezone(\App\Support\GymClock::timezone())->toDateString() !== $request->attendance_date) {
                throw ValidationException::withMessages(['checked_in_at'=>'Check-in must be on the attendance date.']);
            }
            if($request->filled('staff_schedule_id')) {
                $schedule=StaffSchedule::findOrFail($request->staff_schedule_id);
                if($schedule->$column !== $staff->id || $schedule->starts_at->copy()->timezone(\App\Support\GymClock::timezone())->toDateString() !== $request->attendance_date)
                throw ValidationException::withMessages(['staff_schedule_id'=>'Schedule must match this staff member and date.']);
            }
            $attendance=StaffAttendance::updateOrCreate([$column=>$staff->id,'attendance_date'=>$request->attendance_date],
            [...$request->safe()->only(['staff_schedule_id','status','checked_in_at','checked_out_at','notes']),'recorded_by'=>auth()->id()]);
            GymActivity::record('staff.attendance_saved',$attendance);
        }
        );
        return back()->with('status','Attendance saved.');
    }
}



