<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PaymentRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission('finance.manage') ?? false;
    }
    protected function prepareForValidation(): void {
        if ($this->filled('paid_at')) $this->merge(['paid_at'=>\App\Support\GymClock::utcInput($this->paid_at)]);
    }
    public function rules(): array {
        return ['user_id'=>['required','integer','exists:users,id'],'membership_id'=>['nullable','integer','exists:memberships,id'],
        'amount'=>['required','decimal:0,2','gt:0','max:9999999999.99'], 'currency'=>['required','regex:/^[A-Z]{3}$/'],
        'method'=>['required','in:cash,bank_transfer,card,other'], 'status'=>['required','in:pending,paid,failed'],
        'paid_at'=>['nullable','date','before_or_equal:now'], 'reference'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:3000']];
    }
}

