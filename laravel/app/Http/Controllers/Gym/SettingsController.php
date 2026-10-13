<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Models\{GymSetting,AuditLog};
use App\Support\GymActivity;
use Illuminate\Http\Request;
class SettingsController extends Controller {
    public function index() {
        return view('gym.settings',['settings'=>GymSetting::findOrFail(1)]);
    }
    public function update(Request $request) {
        $data=$request->validate(['name'=>['required','string','max:255'],'email'=>['nullable','email','max:254'],
        'phone'=>['nullable','string','max:30','regex:/^[0-9\\s+()\-]{7,30}$/'],'address'=>['nullable','string','max:3000'],
        'timezone'=>['required','timezone','max:80'],'currency'=>['required','regex:/^[A-Z]{3}$/'],'opening_hours'=>['nullable','json',function($attribute,$value,$fail){if(!is_array(json_decode($value,true)))$fail('Opening hours must be a JSON object or list.');}]]);
        $data['opening_hours']=empty($data['opening_hours'])?null:json_decode($data['opening_hours'],true);
        $settings=GymSetting::findOrFail(1);
        $settings->update([...$data,'updated_by'=>auth()->id()]);
        GymActivity::record('gym.settings_updated',$settings);
        return back()->with('status','Gym settings updated; historical currencies and prices retained.');
    }
    public function audit(Request $request) {
        $logs=AuditLog::with('user')->when($request->filled('q'),fn($q)=>$q->where('action','like','%'.$request->q.'%'))->latest()->paginate(30)->withQueryString();
        return view('gym.audit',compact('logs'));
    }
}


