<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class FitnessPlanRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()?->hasGymPermission($this->route('planType').'.manage') ?? false;
    }
    public function rules(): array {
        $diet=$this->route('planType')==='diets';
        $rules=['title'=>['required','string','max:255'],'description'=>['nullable','string','max:5000'],'is_active'=>['required','boolean']];
        if(!$diet) return [...$rules,'exercises'=>['required','array','min:1','max:150'],'exercises.*'=>['integer','distinct','exists:exercises,id']];
        foreach(['breakfast','lunch','dinner','snacks'] as $meal) $rules[$meal]=['required','string','max:4000'];
        $images=collect(json_decode(file_get_contents(public_path('data/diet-plans.json')),true)['diets'])->pluck('image')->all();
        return [...$rules,'image'=>['required',Rule::in($images)],'calories'=>['nullable','string','max:100'],
        'benefits'=>['nullable','string','max:5000'],'tips'=>['nullable','string','max:5000'],'notes'=>['nullable','string','max:5000']];
    }
}
