<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\{MembershipRequest,MembershipPlanRequest};
use App\Models\{User,Membership,MembershipPlan};
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
class MembershipController extends Controller {
    public function index(Request $request) {
        $memberships=Membership::with(['user','plan','payments.refunds'])->when($request->filled('q'),fn($q)=>$q->whereHas('user',fn($u)=>$u->where('name','like','%'.$request->q.'%')))
        ->when($request->filled('status'),function($query) use($request) {
            if($request->status==='cancelled') $query->where('status','cancelled');
            elseif($request->status==='expired') $query->where('status','!=','cancelled')->whereDate('ends_on','<',\App\Support\GymClock::today());
            elseif($request->status==='pending') $query->where('status','!=','cancelled')->whereDate('starts_on','>',\App\Support\GymClock::today());
            elseif($request->status==='active') $query->where('status','!=','cancelled')->whereDate('starts_on','<=',\App\Support\GymClock::today())->whereDate('ends_on','>=',\App\Support\GymClock::today());
        }
        )->latest()->paginate(15)->withQueryString();
        return view('gym.memberships',['memberships'=>$memberships,'clients'=>User::where('role','client')->where('is_active',true)->get(),'plans'=>MembershipPlan::where('is_active',true)->get()]);
    }
    public function store(MembershipRequest $request) {
        return $this->createMembership($request);
    }
    public function renew(MembershipRequest $request,Membership $membership) {
        if($membership->user_id !== (int)$request->user_id) throw ValidationException::withMessages(['user_id'=>'A renewal must use the original Client.']);
        return $this->createMembership($request,$membership);
    }
    private function createMembership(MembershipRequest $request,?Membership $previous=null) {
        DB::transaction(function() use($request,$previous) {
            $client=User::where('role','client')->where('is_active',true)->lockForUpdate()->findOrFail($request->user_id);
            $plan=MembershipPlan::where('is_active',true)->findOrFail($request->membership_plan_id);
            $start=Carbon::parse($request->starts_on)->startOfDay(); $end=$start->copy()->addDays($plan->duration_days-1);
            if(Membership::where('user_id',$client->id)->where('status','!=','cancelled')->where('starts_on','<=',$end->toDateString())->where('ends_on','>=',$start->toDateString())->exists())
            throw ValidationException::withMessages(['starts_on'=>'This Client already has a membership covering these dates.']);
            $record=Membership::create(['user_id'=>$client->id,'membership_plan_id'=>$plan->id,'plan_name'=>$plan->name,
            'duration_days'=>$plan->duration_days,'agreed_price'=>$plan->price,'currency'=>$plan->currency,
            'starts_on'=>$start,'ends_on'=>$end,'status'=>$end->isBefore(\App\Support\GymClock::today())?'expired':($start->isAfter(\App\Support\GymClock::today())?'pending':'active'),
            'created_by'=>auth()->id(),'notes'=>$request->notes]);
            GymActivity::record($previous?'membership.renewed':'membership.created',$record);
            GymActivity::notify($client,'Membership updated',$record->plan_name.' starts '.$start->toDateString().'.');
        }
        );
        return back()->with('status','Membership saved with its original price and plan details.');
    }
    public function cancel(Membership $membership) {
        DB::transaction(function()use($membership){
            $membership=Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            $membership->update(['status'=>'cancelled','cancelled_at'=>$membership->cancelled_at??now()]);
            GymActivity::record('membership.cancelled',$membership);
        },3);
        return back()->with('status','Membership cancelled; payment history retained.');
    }
    public function plans() {
        return view('gym.membership-plans',['plans'=>MembershipPlan::withTrashed()->latest()->paginate(15)]);
    }
    public function savePlan(MembershipPlanRequest $request,?int $record=null) {
        $plan=$record?MembershipPlan::withTrashed()->findOrFail($record):new MembershipPlan();
        if(!$plan->exists) {
            $plan->slug=Str::slug($request->name).'-'.Str::lower(Str::random(8));
            $plan->created_by=auth()->id();
        }
        if($plan->trashed())$plan->restore();
        $plan->fill($request->validated())->save();
        GymActivity::record('membership_plan.saved',$plan);
        return back()->with('status','Plan saved; existing membership prices are unchanged.');
    }
    public function archivePlan(MembershipPlan $plan) {
        $plan->update(['is_active'=>false]);
        $plan->delete();
        GymActivity::record('membership_plan.archived',$plan);
        return back()->with('status','Plan archived.');
    }
}



