<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\FitnessPlanRequest;
use App\Models\{User,WorkoutPlan,DietPlan,Exercise,ClientPlanAssignment};
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
use Illuminate\Support\Str;
class FitnessPlanController extends Controller {
    private function model(string $type): string {
        Gate::authorize($type.'.manage');
        return $type==='workouts'?WorkoutPlan::class:DietPlan::class;
    }
    public function index(Request $request,string $planType) {
        $model=$this->model($planType);
        $plans=$model::withTrashed()->when($request->filled('q'),fn($q)=>$q->where('title','like','%'.$request->q.'%'))->latest()->paginate(15)->withQueryString();
        $assignments=ClientPlanAssignment::with(['user','workoutPlan','dietPlan'])->whereNotNull($planType==='workouts'?'workout_plan_id':'diet_plan_id')->latest()->paginate(15,['*'],'assignments_page');
        $clients=User::where('role','client')->where('is_active',true)->get();
        return view('gym.fitness-plans',compact('plans','planType','assignments','clients'));
    }
    public function form(string $planType,?int $record=null) {
        $model=$this->model($planType);
        $plan=$record?$model::withTrashed()->findOrFail($record):new $model();
        $exercises=Exercise::with('category')->orderBy('workout_category_id')->orderBy('sort_order')->get();
        $images=collect(json_decode(file_get_contents(public_path('data/diet-plans.json')),true)['diets'])->pluck('image')->unique();
        return view('gym.fitness-plan-form',compact('plan','planType','exercises','images'));
    }
    public function save(FitnessPlanRequest $request,string $planType,?int $record=null) {
        $model=$this->model($planType);
        DB::transaction(function() use($request,$model,$planType,$record) {
            $plan=$record?$model::withTrashed()->findOrFail($record):new $model();
            if($plan->trashed())$plan->restore();
            $data=$request->safe()->except(['exercises','benefits','tips','description']);
            if($planType==='workouts') $data['description']=$request->description;
            else foreach(['benefits','tips'] as $field) $data[$field]=array_values(array_filter(array_map('trim',preg_split('/\R/',(string)$request->$field))));
            if(!$plan->exists) {
                if($planType==='diets') {
                    $data['icon']='bi bi-egg-fried';$data['sort_order']=DietPlan::withTrashed()->max('sort_order')+1;
                }
                $data['slug']=Str::slug($request->title).'-'.Str::lower(Str::random(8));$data['created_by']=auth()->id();
            }
            $plan->fill($data)->save();
            if($planType==='workouts') {
                $plan->planExercises()->delete();
                foreach($request->validated('exercises') as $order=>$exerciseId) {
                    $exercise=Exercise::findOrFail($exerciseId);
                    $plan->planExercises()->create(['exercise_id'=>$exercise->id,'sets'=>$exercise->sets,'reps'=>$exercise->reps,'sort_order'=>$order]);
                }
            }
            GymActivity::record('fitness_plan.saved',$plan);
        }
        );
        return redirect()->route('gym.fitness-plans',$planType)->with('status','Fitness plan saved.');
    }
    public function archive(string $planType,int $record) {
        $model=$this->model($planType);
        $plan=$model::findOrFail($record);
        $plan->update(['is_active'=>false]);
        $plan->delete();
        GymActivity::record('fitness_plan.archived',$plan);
        return back()->with('status','Plan archived; past assignments retained.');
    }
    public function assign(Request $request,string $planType) {
        $model=$this->model($planType);
        $data=$request->validate(['user_id'=>['required','integer','exists:users,id'],'plan_id'=>['required','integer'],
        'starts_on'=>['required','date'],'ends_on'=>['nullable','date','after_or_equal:starts_on'],'notes'=>['nullable','string','max:3000']]);
        $client=User::where('role','client')->where('is_active',true)->findOrFail($data['user_id']);
        $plan=$model::where('is_active',true)->findOrFail($data['plan_id']);
        DB::transaction(function() use($data,$client,$plan,$planType) {
            $assignment=ClientPlanAssignment::create(['user_id'=>$client->id,($planType==='workouts'?'workout_plan_id':'diet_plan_id')=>$plan->id,
            'assigned_by'=>auth()->id(),'starts_on'=>$data['starts_on'],'ends_on'=>$data['ends_on']??null,'notes'=>$data['notes']??null]);
            GymActivity::record('fitness_plan.assigned',$assignment);GymActivity::notify($client,'New fitness plan',$plan->title.' has been assigned to you.');
        }
        );
        return back()->with('status','Plan assigned to Client.');
    }
    public function endAssignment(string $planType,ClientPlanAssignment $assignment) {
        $this->model($planType);
        abort_unless($planType==='workouts'?$assignment->workout_plan_id:$assignment->diet_plan_id,404);
        $assignment->update(['cancelled_at'=>$assignment->cancelled_at??now()]);
        GymActivity::record('fitness_plan.assignment_ended',$assignment);
        return back()->with('status','Assignment ended; history retained.');
    }
}
