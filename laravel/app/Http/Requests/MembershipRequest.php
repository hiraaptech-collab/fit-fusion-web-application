<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class MembershipRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission('memberships.manage') ?? false;
    }
    public function rules(): array {
        return ['user_id'=>['required','integer','exists:users,id'], 'membership_plan_id'=>['required','integer','exists:membership_plans,id'],
        'starts_on'=>['required','date'], 'notes'=>['nullable','string','max:3000']];
    }
}
