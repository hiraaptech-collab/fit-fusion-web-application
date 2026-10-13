<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AttendanceRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission('staff.attendance') ?? false;
    }
    protected function prepareForValidation(): void {
        foreach(['checked_in_at','checked_out_at'] as $field) if($this->filled($field)) $this->merge([$field=>\App\Support\GymClock::utcInput($this->input($field))]);
        // The combined selector keeps Trainer and Worker IDs distinct.
        if ($this->has('staff_key')) {
            $parts = explode(':', (string) $this->staff_key, 2);
            $this->merge(['staff_type'=>$parts[0]??'', 'staff_id'=>$parts[1]??'']);
        }
    }
    public function rules(): array {
        return ['staff_type'=>['required','in:trainer,worker'], 'staff_id'=>['required','integer'],
        'staff_schedule_id'=>['nullable','integer','exists:staff_schedules,id'],
        'attendance_date'=>['required','date','before_or_equal:'.\App\Support\GymClock::today()->toDateString()], 'status'=>['required','in:present,absent,late,leave'],
        'checked_in_at'=>['nullable','date','required_with:checked_out_at'], 'checked_out_at'=>['nullable','date','after_or_equal:checked_in_at'],
        'notes'=>['nullable','string','max:3000']];
    }
}

