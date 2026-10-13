<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\{ProfileRequest,ProgressRequest};
use App\Models\FitnessProgress;
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
class ClientController extends Controller {
    public function profile() {
        return view('gym.profile',['user'=>auth()->user(),'profile'=>auth()->user()->clientProfile]);
    }
    public function updateProfile(ProfileRequest $request) {
        DB::transaction(function() use($request) {
            $user=$request->user();$user->update(['name'=>$request->name]);
            $user->clientProfile()->updateOrCreate(['user_id'=>$user->id],[...$request->safe()->except(['name','fitness_consent']),
            'fitness_data_consent_at'=>$request->boolean('fitness_consent')?($user->clientProfile?->fitness_data_consent_at??now()):null]);
            GymActivity::record('client.profile_updated',$user);
        }
        );
        return back()->with('status','Profile saved.');
    }
    public function memberships() {
        return view('gym.client-memberships',['memberships'=>auth()->user()->memberships()->with(['plan','payments.refunds'])->latest()->paginate(15)]);
    }
    public function plans() {
        return view('gym.client-plans',['assignments'=>auth()->user()->planAssignments()->with(['workoutPlan.planExercises.exercise','dietPlan'])->latest()->paginate(15)]);
    }
    public function payments() {
        return view('gym.client-payments',['payments'=>auth()->user()->payments()->with('refunds')->latest()->paginate(15)]);
    }
    public function progress() {
        return view('gym.progress',['entries'=>auth()->user()->fitnessProgress()->latest('recorded_on')->paginate(20)]);
    }
    public function saveProgress(ProgressRequest $request) {
        $bmi=round($request->weight_kg/pow($request->height_cm/100,2),2);
        if($bmi>999) throw \Illuminate\Validation\ValidationException::withMessages(['weight_kg'=>'Height and weight produce an unsupported BMI.']);
        DB::transaction(function() use($request,$bmi) {
            $user=$request->user();$user->clientProfile()->updateOrCreate(['user_id'=>$user->id],['fitness_data_consent_at'=>now()]);
            $entry=$user->fitnessProgress()->updateOrCreate(['recorded_on'=>$request->recorded_on],[
            ...$request->safe()->only(['weight_kg','height_cm','body_fat_percent','notes']),'bmi'=>$bmi,
            'measurements'=>$request->filled('waist_cm')?['waist_cm'=>(float)$request->waist_cm]:null]);
            GymActivity::record('client.progress_saved',$entry);
        }
        );
        return $request->expectsJson()?response()->json(['saved'=>true,'bmi'=>$bmi]):back()->with('status','Progress saved.');
    }
    public function deleteProgress(FitnessProgress $progress) {
        Gate::authorize('update',$progress);
        GymActivity::record('client.progress_removed',$progress);
        $progress->delete();
        return back()->with('status','Personal progress entry removed.');
    }
}
