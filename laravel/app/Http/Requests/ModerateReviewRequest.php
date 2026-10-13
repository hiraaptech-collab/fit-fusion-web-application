<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ModerateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasGymPermission('feedback.manage') ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', 'in:approved,rejected,hidden']];
    }
}
