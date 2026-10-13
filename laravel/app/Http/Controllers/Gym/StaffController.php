<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\StaffRequest;
use App\Models\Trainer;
use App\Models\Worker;
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
class StaffController extends Controller {
    private function model(string $type): string {
        Gate::authorize($type.'.manage');
        return $type === 'trainers' ? Trainer::class : Worker::class;
    }
    public function index(Request $request, string $staffType) {
        $model = $this->model($staffType);
        $records = $model::withTrashed()->when($request->filled('q'),fn($q)=>$q->where('name','like','%'.$request->q.'%'))->orderBy('name')->paginate(15)->withQueryString();
        return view('gym.staff',compact('records','staffType'));
    }
    public function edit(string $staffType,int $record) {
        $model = $this->model($staffType);
        $staff = $model::withTrashed()->findOrFail($record);
        return view('gym.staff-form',compact('staff','staffType'));
    }
    public function store(StaffRequest $request,string $staffType) {
        $model = $this->model($staffType);
        $data = $request->safe()->only($staffType === 'trainers' ? ['name','email','phone','hired_on','is_active','specialties','qualifications'] : ['name','email','phone','hired_on','is_active','job_title']);
        $record = $model::create([...$data,'created_by'=>auth()->id()]);
        GymActivity::record('staff.created',$record);
        return back()->with('status','Staff record created.');
    }
    public function update(StaffRequest $request,string $staffType,int $record) {
        $model = $this->model($staffType);
        $staff = $model::withTrashed()->findOrFail($record);
        $data = $request->safe()->only($staffType === 'trainers' ? ['name','email','phone','hired_on','is_active','specialties','qualifications'] : ['name','email','phone','hired_on','is_active','job_title']);
        if($staff->trashed()) $staff->restore();
        $staff->update($data);
        GymActivity::record('staff.updated',$staff);
        return redirect()->route('gym.staff',$staffType)->with('status','Staff record updated.');
    }
    public function archive(string $staffType,int $record) {
        $model = $this->model($staffType);
        $staff = $model::findOrFail($record);
        $staff->update(['is_active'=>false]);
        $staff->delete();
        GymActivity::record('staff.archived',$staff);
        return back()->with('status','Staff archived; historical records retained.');
    }
}
