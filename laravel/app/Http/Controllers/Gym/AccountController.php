<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountRequest;
use App\Models\User;
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AccountController extends Controller {
    private function authorizeType(string $accountType): void {
        abort_unless($accountType === 'managers' ? auth()->user()->role === 'owner'
        : auth()->user()->hasGymPermission('clients.manage'), 403);
    }
    public function index(Request $request, string $accountType) {
        $this->authorizeType($accountType);
        $role = $accountType === 'managers' ? 'manager' : 'client';
        $records = User::where('role',$role)->when($request->filled('q'), fn($q) =>
        $q->where(fn($query) => $query->where('name','like','%'.$request->q.'%')->orWhere('email','like','%'.$request->q.'%')))->orderBy('name')->paginate(15)->withQueryString();
        return view('gym.accounts', compact('records','accountType'));
    }
    public function edit(string $accountType, int $record) {
        $this->authorizeType($accountType);
        $user = User::where('role',$accountType === 'managers' ? 'manager' : 'client')->findOrFail($record);
        return view('gym.account-form', compact('user','accountType'));
    }
    public function store(AccountRequest $request, string $accountType) {
        $this->authorizeType($accountType);
        DB::transaction(function() use($request,$accountType) {
            $user = new User($request->safe()->only(['name','email','password']));
            $user->role = $accountType === 'managers' ? 'manager' : 'client';
            $user->is_active = $request->boolean('is_active'); $user->save();
            if($user->role === 'client') $user->clientProfile()->create($this->profileData($request));
            else $this->permissions($user,$request->validated('permissions',[]));
            GymActivity::record('account.created',$user);
        }
        );
        return back()->with('status','Account created.');
    }
    public function update(AccountRequest $request, string $accountType, int $record) {
        $this->authorizeType($accountType);
        $user = User::where('role',$accountType === 'managers' ? 'manager' : 'client')->findOrFail($record);
        DB::transaction(function() use($request,$user) {
            $user->fill($request->safe()->only(['name','email']));
            if($request->filled('password')) $user->password = $request->password;
            $user->is_active = $request->boolean('is_active'); $user->save();
            if($user->role === 'client') $user->clientProfile()->updateOrCreate(['user_id'=>$user->id],$this->profileData($request));
            if($user->role === 'manager') $this->permissions($user,$request->validated('permissions',[]));
            GymActivity::record('account.updated',$user);
        }
        );
        return redirect()->route('gym.accounts',$accountType)->with('status','Account updated.');
    }
    private function profileData(AccountRequest $request): array {
        // Account management cannot grant fitness-data consent on a Client's behalf.
        return $request->safe()->only(['phone','date_of_birth','gender','address','emergency_contact_name','emergency_contact_phone','fitness_goal']);
    }
    private function permissions(User $manager, array $permissions): void {
        $manager->managerPermissions()->delete();
        foreach($permissions as $permission) $manager->managerPermissions()->create(['permission'=>$permission,'granted_by'=>auth()->id()]);
    }
}

