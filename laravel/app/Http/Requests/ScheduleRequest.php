<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ScheduleRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission('staff.schedules') ?? false;
    }
    protected function prepareForValidation(): void {
        foreach(['starts_at','ends_at'] as $field) if($this->filled($field)) $this->merge([$field=>\App\Support\GymClock::utcInput($this->input($field))]);
        // The combined selector keeps Trainer and Worker IDs distinct.
        if ($this->has('staff_key')) {
            $parts = explode(':', (string) $this->staff_key, 2);
            $this->merge(['staff_type'=>$parts[0]??'', 'staff_id'=>$parts[1]??'']);
        }
    }
    public function rules(): array {
        return ['staff_type'=>['required',Rule::in(['trainer','worker'])], 'staff_id'=>['required','integer'],
        'starts_at'=>['required','date'], 'ends_at'=>['required','date','after:starts_at'], 'notes'=>['nullable','string','max:3000']];
    }
}

