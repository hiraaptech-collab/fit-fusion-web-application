<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ProgressRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->role==='client';
    }
    public function rules(): array {
        return ['recorded_on'=>['required','date','before_or_equal:'.\App\Support\GymClock::today()->toDateString()],
        'weight_kg'=>['required','numeric','between:1,700'],'height_cm'=>['required','numeric','between:30,250'],
        'body_fat_percent'=>['nullable','numeric','between:0,100'],'waist_cm'=>['nullable','numeric','between:10,400'],
        'notes'=>['nullable','string','max:3000'],'consent'=>['accepted']];
    }
}
