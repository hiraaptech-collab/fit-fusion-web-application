<?php
namespace App\Http\Requests;
use App\Rules\PasswordLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
class AccountRequest extends FormRequest {
    public function authorize(): bool {
        return $this->route('accountType') === 'managers' ? $this->user()?->role === 'owner'
        : ($this->user()?->hasGymPermission('clients.manage') ?? false);
    }
    protected function prepareForValidation(): void {
        $this->merge(['email' => mb_strtolower(trim((string)$this->email))]);
    }
    public function rules(): array {
        return ['name' => ['required','string','max:255'],
        'email' => ['required','email','max:254',Rule::unique('users')->ignore($this->route('record'))],
        'password' => [$this->route('record') ? 'nullable' : 'required','string','confirmed','max:72',new PasswordLength(),Password::min(12)->mixedCase()->numbers()],
        'is_active' => ['required','boolean'], 'role' => ['prohibited'],
        'phone'=>['nullable','string','max:30'],'date_of_birth'=>['nullable','date','before_or_equal:'.\App\Support\GymClock::today()->toDateString()],
        'gender'=>['nullable','string','max:30'],'address'=>['nullable','string','max:3000'],
        'emergency_contact_name'=>['nullable','string','max:255'],'emergency_contact_phone'=>['nullable','string','max:30'],'fitness_goal'=>['nullable','string','max:3000'],
        'permissions' => ['sometimes','array','max:'.count(config('gym.manager_permissions'))],
        'permissions.*' => ['string','distinct',Rule::in(array_keys(config('gym.manager_permissions')))]];
    }
}


