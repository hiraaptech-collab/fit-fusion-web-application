<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class MembershipPlanRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission('memberships.manage') ?? false;
    }
    public function rules(): array {
        return ['name'=>['required','string','max:255'],'description'=>['nullable','string','max:5000'],
        'duration_days'=>['required','integer','min:1','max:3650'], 'price'=>['required','decimal:0,2','min:0','max:9999999999.99'],
        'currency'=>['required','regex:/^[A-Z]{3}$/'], 'is_active'=>['required','boolean']];
    }
}
