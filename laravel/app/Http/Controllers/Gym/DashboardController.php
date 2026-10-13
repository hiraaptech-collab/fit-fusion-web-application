<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Models\{User,Trainer,Worker,Membership,Payment,Refund,Expense,FitnessProgress};
use Illuminate\Http\Request;
class DashboardController extends Controller {
    public function redirect(Request $request) {
        return redirect()->route($request->user()->role.'.dashboard');
    }
    public function management(Request $request) {
        $user=$request->user();
        $cards=[];
        if($user->hasGymPermission('clients.manage'))$cards['Clients']=User::where('role','client')->count();
        if($user->role==='owner')$cards['Managers']=User::where('role','manager')->count();
        if($user->hasGymPermission('memberships.manage'))$cards['Active memberships']=Membership::where('status','!=','cancelled')->whereDate('starts_on','<=',\App\Support\GymClock::today())->whereDate('ends_on','>=',\App\Support\GymClock::today())->count();
        if($user->hasGymPermission('trainers.manage'))$cards['Active trainers']=Trainer::where('is_active',true)->count();
        if($user->hasGymPermission('workers.manage'))$cards['Active workers']=Worker::where('is_active',true)->count();
        if ($user->hasGymPermission('feedback.manage')) $cards['Pending reviews']=\App\Models\ClientReview::where('status','pending')->count();
        $finance=$user->hasGymPermission('finance.view')?$this->financeTotals():[];
        return view('gym.dashboard',compact('cards','finance'));
    }
    public function client(Request $request) {
        $user=$request->user();
        $membership=$user->memberships()->where('status','!=','cancelled')->orderByRaw('CASE WHEN starts_on <= ? AND ends_on >= ? THEN 0 ELSE 1 END', [\App\Support\GymClock::today()->toDateString(),\App\Support\GymClock::today()->toDateString()])->latest('ends_on')->first();
        $progress=$user->fitnessProgress()->latest('recorded_on')->first();
        $assignments=$user->planAssignments()->with(['workoutPlan','dietPlan'])->whereNull('cancelled_at')->whereDate('starts_on','<=',\App\Support\GymClock::today())->where(fn($q)=>$q->whereNull('ends_on')->orWhereDate('ends_on','>=',\App\Support\GymClock::today()))->get();
        return view('gym.client-dashboard',compact('membership','progress','assignments'));
    }
    private function financeTotals(): array
    {
        $paid=Payment::where('status','paid')->selectRaw('currency, SUM(amount) AS total')->groupBy('currency')->pluck('total','currency');
        $refunds=Refund::where('status','completed')->selectRaw('currency, SUM(amount) AS total')->groupBy('currency')->pluck('total','currency');
        $expenses=Expense::selectRaw('currency, SUM(amount) AS total')->groupBy('currency')->pluck('total','currency');
        $result=[];
        foreach($paid->keys()->merge($refunds->keys())->merge($expenses->keys())->unique() as $currency){
            $netRevenue=(float)($paid[$currency]??0)-(float)($refunds[$currency]??0);
            $spent=(float)($expenses[$currency]??0);
            $result[$currency]=['revenue'=>$netRevenue,'refunds'=>(float)($refunds[$currency]??0),'expenses'=>$spent,'net'=>$netRevenue-$spent];
        }
        return $result;
    }

    public function report(Request $request)
    {
        $request->validate(['months'=>['nullable','integer','in:3,6,12'],'currency'=>['nullable','regex:/^[A-Z]{3}$/']]);
        $months=(int)$request->input('months',6);
        $currency=$request->input('currency',\App\Models\GymSetting::find(1)->currency);
        $canFinance=$request->user()->hasGymPermission('finance.view');
        $dates=[];$timestamps=[];$rows=[];
        $month=\App\Support\GymClock::today()->startOfMonth()->subMonthsNoOverflow($months-1);
        for($index=0;$index<$months;$index++){
            $start=$month->copy()->addMonthsNoOverflow($index);$end=$start->copy()->endOfMonth();
            $dates[]=[$start->toDateString(),$end->toDateString()];
            $timestamps[]=[\App\Support\GymClock::utcInput($start->toDateTimeString()),\App\Support\GymClock::utcInput($end->toDateTimeString())];
            $rows[]=['month'=>$start->format('M Y')];
        }
        $clients=$this->monthlyValues(User::class,'created_at',$timestamps,['role'=>'client']);
        $memberships=$this->monthlyValues(Membership::class,'starts_on',$dates,[]);
        if($canFinance){
            $paid=$this->monthlyValues(Payment::class,'paid_at',$timestamps,['currency'=>$currency,'status'=>'paid'],'amount');
            $refunds=$this->monthlyValues(Refund::class,'refunded_at',$timestamps,['currency'=>$currency,'status'=>'completed'],'amount');
            $expenses=$this->monthlyValues(Expense::class,'incurred_on',$dates,['currency'=>$currency],'amount');
        }
        foreach($rows as $index=>&$row){
            $row+=['clients'=>(int)$clients->{'month_'.$index},'memberships'=>(int)$memberships->{'month_'.$index}];
            if($canFinance){$revenue=(float)$paid->{'month_'.$index}-(float)$refunds->{'month_'.$index};$spent=(float)$expenses->{'month_'.$index};$row+=['revenue'=>$revenue,'expenses'=>$spent,'net'=>$revenue-$spent];}
        }
        unset($row);
        return $request->expectsJson()?response()->json(['rows'=>$rows,'currency'=>$currency,'financial'=>$canFinance]):view('gym.reports',compact('rows','currency','canFinance','months'));
    }

    private function monthlyValues(string $model,string $dateColumn,array $periods,array $filters,?string $amount=null)
    {
        $query=$model::query()->whereBetween($dateColumn,[$periods[0][0],$periods[array_key_last($periods)][1]]);
        foreach($filters as $column=>$value)$query->where($column,$value);
        $expressions=[];$bindings=[];
        foreach($periods as $index=>[$start,$end]){
            // Column names are internal constants; all dates are bound parameters.
            $value=$amount??'1';
            $expressions[]="COALESCE(SUM(CASE WHEN $dateColumn >= ? AND $dateColumn <= ? THEN $value ELSE 0 END),0) AS month_$index";
            array_push($bindings,$start,$end);
        }
        return $query->selectRaw(implode(',',$expressions),$bindings)->first();
    }
}
