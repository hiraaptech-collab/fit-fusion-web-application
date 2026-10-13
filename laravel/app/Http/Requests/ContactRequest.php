<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ContactRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }
    public function rules(): array {
        return ['fullName'=>['required','string','min:2','max:255'],'email'=>['required','email','max:254'],
        'phone'=>['required','string','regex:/^[\d\s+\-()]{7,20}$/'],
        'subject'=>['required','string','min:3','max:255'],'message'=>['required','string','min:10','max:10000']];
    }
}
