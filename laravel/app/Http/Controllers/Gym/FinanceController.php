<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\{PaymentRequest,ExpenseRequest};
use App\Models\{User,Membership,Payment,Refund,Expense,GymSetting};
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class FinanceController extends Controller {
    public function index(Request $request) {
        abort_unless(auth()->user()->hasGymPermission('finance.view')||auth()->user()->hasGymPermission('finance.manage'),403);
        $canView=auth()->user()->hasGymPermission('finance.view');
        $payments=$canView?Payment::with(['user','membership','refunds'])->when($request->filled('status'),fn($q)=>$q->where('status',$request->status))->latest()->paginate(15,['*'],'payments_page')->withQueryString():null;
        $expenses=$canView?Expense::latest('incurred_on')->paginate(15,['*'],'expenses_page'):null;
        $refunds=$canView?Refund::with('payment.user')->latest()->paginate(15,['*'],'refunds_page'):null;
        $totals=$canView?Payment::where('status','paid')->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total','currency'):collect();
        $expenseTotals=$canView?Expense::selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total','currency'):collect();
        $refundTotals=$canView?Refund::where('status','completed')->selectRaw('currency, SUM(amount) as total')->groupBy('currency')->pluck('total','currency'):collect();
        $clients=User::where('role','client')->get();
        $memberships=Membership::with('user')->where('status','!=','cancelled')->latest()->get();
        $currency=GymSetting::find(1)->currency;
        return view('gym.finance',compact('canView','payments','expenses','refunds','totals','expenseTotals','refundTotals','clients','memberships','currency'));
    }
    public function payment(PaymentRequest $request) {
        DB::transaction(function() use($request) {
            $client=User::where('role','client')->lockForUpdate()->findOrFail($request->user_id);
            if($request->filled('membership_id')) {
                $membership=Membership::lockForUpdate()->findOrFail($request->membership_id);
                if($membership->user_id!==$client->id||$membership->currency!==$request->currency||$membership->status==='cancelled')
                throw ValidationException::withMessages(['membership_id'=>'Choose this Client\'s uncancelled membership in the same currency.']);
                if($request->status==='paid') $this->checkBalance($membership,$request->amount);
            }
            $payment=Payment::create([...$request->validated(),'receipt_number'=>'FF-'.Str::upper(Str::random(14)),
            'recorded_by'=>auth()->id(),'paid_at'=>$request->status==='paid'?($request->paid_at??now()):null]);
            GymActivity::record('payment.recorded',$payment);GymActivity::notify($client,'Payment recorded',$payment->receipt_number.': '.$payment->currency.' '.$payment->amount);
        }
        );
        return back()->with('status','Payment recorded.');
    }
    private function checkBalance(Membership $membership,string $amount,?int $except=null): void {
        $paid=$membership->payments()->where('status','paid')->when($except,fn($q)=>$q->where('id','!=',$except))->sum('amount');
        $refunded=Refund::whereHas('payment',fn($q)=>$q->where('membership_id',$membership->id)->where('status','paid'))->where('status','completed')->sum('amount');
        if(GymActivity::cents($paid)-GymActivity::cents($refunded)+GymActivity::cents($amount)>GymActivity::cents($membership->agreed_price))
        throw ValidationException::withMessages(['amount'=>'Payment exceeds the remaining membership balance.']);
    }
    public function paymentStatus(Request $request,Payment $payment) {
        Gate::authorize('finance.manage');
        $request->validate(['status'=>['required','in:paid,failed,void']]);
        DB::transaction(function() use($request,$payment) {
            // All finance writes lock Client, membership, then payment in that order.
            User::whereKey($payment->user_id)->lockForUpdate()->firstOrFail();
            $membership=$payment->membership_id?Membership::lockForUpdate()->findOrFail($payment->membership_id):null;
            $payment=Payment::lockForUpdate()->findOrFail($payment->id);
            if($payment->status==='paid'||$payment->status==='void') throw ValidationException::withMessages(['status'=>'Settled payments are immutable; record a refund instead.']);
            if($request->status==='paid'&&$membership?->status==='cancelled') throw ValidationException::withMessages(['status'=>'Cancelled memberships cannot receive payments.']);
            if($request->status==='paid'&&$membership) $this->checkBalance($membership,$payment->amount,$payment->id);
            $payment->update(['status'=>$request->status,'paid_at'=>$request->status==='paid'?now():null]);GymActivity::record('payment.status_updated',$payment);
        }
        );
        return back()->with('status','Payment status updated.');
    }
    public function refund(Request $request,Payment $payment) {
        Gate::authorize('finance.manage');
        $data=$request->validate(['amount'=>['required','decimal:0,2','gt:0','max:9999999999.99'],'reason'=>['required','string','max:3000']]);
        DB::transaction(function() use($payment,$data) {
            User::whereKey($payment->user_id)->lockForUpdate()->firstOrFail();
            if($payment->membership_id) Membership::whereKey($payment->membership_id)->lockForUpdate()->firstOrFail();
            $payment=Payment::lockForUpdate()->findOrFail($payment->id);
            if($payment->status!=='paid'||GymActivity::cents($payment->refunds()->whereIn('status',['pending','completed'])->sum('amount'))+GymActivity::cents($data['amount'])>GymActivity::cents($payment->amount))
            throw ValidationException::withMessages(['amount'=>'Refund exceeds the remaining paid amount.']);
            $refund=$payment->refunds()->create([...$data,'reference'=>'RF-'.Str::upper(Str::random(14)),'currency'=>$payment->currency,'status'=>'completed','refunded_at'=>now(),'recorded_by'=>auth()->id()]);
            GymActivity::record('refund.recorded',$refund);GymActivity::notify($payment->user,'Refund recorded',$refund->currency.' '.$refund->amount.' refunded.');
        }
        );
        return back()->with('status','Refund recorded; original payment retained.');
    }
    public function expense(ExpenseRequest $request) {
        DB::transaction(function()use($request){
            $expense=Expense::create([...$request->validated(),'recorded_by'=>auth()->id()]);
            GymActivity::record('expense.recorded',$expense);
        },3);
        return back()->with('status','Expense recorded.');
    }
}

