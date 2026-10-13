<?php
namespace App\Http\Controllers;
use App\Models\{WorkoutCategory,DietPlan,MembershipPlan,GymSetting};
class PublicDataController extends Controller {
    public function workouts() {
        $categories=WorkoutCategory::with(['exercises'=>fn($q)=>$q->orderBy('sort_order')])->orderBy('sort_order')->get();
        return response()->json(['categories'=>$categories->map(fn($c)=>[
        'id'=>$c->slug,'title'=>$c->title,'icon'=>$c->icon,'description'=>$c->description,
        'exercises'=>$c->exercises->map(fn($e)=>$e->only(['name','sets','reps','difficulty','calories','muscle','tips','image']))])]);
    }
    public function diets() {
        return response()->json(['diets'=>DietPlan::where('is_active',true)->orderBy('sort_order')->get()->map(fn($d)=>[
        'id'=>$d->slug,...$d->only(['title','icon','image','calories','breakfast','lunch','dinner','snacks','benefits','tips','notes'])])]);
    }
    public function configuration() {
        $number=preg_replace('/\D/','',GymSetting::find(1)?->phone ?? '');
        if(!$number) $number=preg_replace('/\D/','',config('fitfusion.whatsapp_number'));
        return response()->json(['whatsappNumber'=>$number ?: '0000000000']);
    }
    public function memberships() {
        return view('pages.memberships',['plans'=>MembershipPlan::where('is_active',true)->orderBy('price')->get()]);
    }
}

