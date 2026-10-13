<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StaffRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission($this->route('staffType').'.manage') ?? false;
    }
    public function rules(): array {
        $type = $this->route('staffType');
        return ['name' => ['required','string','max:255'],
        'email' => ['nullable','email','max:254',Rule::unique($type)->ignore($this->route('record'))],
        'phone' => ['nullable','string','max:30'], 'hired_on' => ['nullable','date'],
        'is_active' => ['required','boolean'], 'specialties' => ['nullable','string','max:3000'],
        'qualifications' => ['nullable','string','max:3000'],
        'job_title' => [$type === 'workers' ? 'required' : 'nullable','string','max:255']];
    }
}
