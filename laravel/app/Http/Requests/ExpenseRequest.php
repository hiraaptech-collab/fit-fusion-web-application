<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ExpenseRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission('finance.manage') ?? false;
    }
    public function rules(): array {
        return ['description'=>['required','string','max:255'],'category'=>['required','string','max:80'],
        'amount'=>['required','decimal:0,2','gt:0','max:9999999999.99'],'currency'=>['required','regex:/^[A-Z]{3}$/'],
        'incurred_on'=>['required','date','before_or_equal:'.\App\Support\GymClock::today()->toDateString()],'reference'=>['nullable','string','max:255'],'notes'=>['nullable','string','max:3000']];
    }
}
