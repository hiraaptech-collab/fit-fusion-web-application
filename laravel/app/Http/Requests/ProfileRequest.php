<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProfileRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->role==='client';
    }
    public function rules(): array {
        return ['name'=>['required','string','max:255'],'phone'=>['nullable','string','max:30'],
        'date_of_birth'=>['nullable','date','before_or_equal:'.\App\Support\GymClock::today()->toDateString()],'gender'=>['nullable','string','max:30'],
        'address'=>['nullable','string','max:3000'],'emergency_contact_name'=>['nullable','string','max:255'],
        'emergency_contact_phone'=>['nullable','string','max:30'],'fitness_goal'=>['nullable','string','max:3000'],
        'fitness_consent'=>['required','boolean']];
    }
}
