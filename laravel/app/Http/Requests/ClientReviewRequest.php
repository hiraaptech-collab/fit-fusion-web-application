<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'client' && $this->user()->is_active;
    }

    protected function prepareForValidation(): void
    {
        foreach (['display_name', 'message'] as $field) {
            if (is_string($this->input($field))) $this->merge([$field => trim($this->input($field))]);
        }
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'min:2', 'max:40', "regex:/\A[\p{L}\p{M}\p{N} .'-]+\z/u"],
            'public_consent' => ['accepted'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
            'user_id' => ['prohibited'], 'status' => ['prohibited'],
            'reviewed_by' => ['prohibited'], 'reviewed_at' => ['prohibited'],
            'created_at' => ['prohibited'], 'review_hash' => ['prohibited'],
        ];
    }
}

