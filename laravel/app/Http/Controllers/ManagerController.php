<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Rules\PasswordLength;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ManagerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'max:72', new PasswordLength(), Password::min(12)->mixedCase()->numbers()],
            'permissions' => ['sometimes', 'array', 'max:'.count(config('gym.manager_permissions'))],
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(config('gym.manager_permissions')))],
            'role' => ['prohibited'],
            'is_active' => ['prohibited'],
        ]);
        $manager = DB::transaction(function () use ($data, $request) {
            $manager = new User(['name' => $data['name'], 'email' => mb_strtolower(trim($data['email'])), 'password' => $data['password']]);
            $manager->role = 'manager';
            $manager->is_active = true;
            $manager->save();
            $this->savePermissions($manager, $data['permissions'] ?? [], $request->user());
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'manager.created', 'entity_type' => 'user', 'entity_id' => $manager->id, 'ip_address' => $request->ip()]);
            return $manager;
        });
        return response()->json(['manager' => $manager->only('id', 'name', 'email', 'role', 'is_active')], 201);
    }

    public function permissions(Request $request, User $manager): JsonResponse
    {
        abort_unless($manager->role === 'manager', 404);
        $data = $request->validate([
            'permissions' => ['present', 'array', 'max:'.count(config('gym.manager_permissions'))],
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(config('gym.manager_permissions')))],
        ]);
        DB::transaction(function () use ($data, $request, $manager) {
            $manager->managerPermissions()->delete();
            $this->savePermissions($manager, $data['permissions'], $request->user());
            AuditLog::create(['user_id' => $request->user()->id, 'action' => 'manager.permissions_updated', 'entity_type' => 'user', 'entity_id' => $manager->id, 'ip_address' => $request->ip()]);
        });
        return response()->json(['permissions' => $data['permissions']]);
    }

    private function savePermissions(User $manager, array $permissions, User $owner): void
    {
        foreach ($permissions as $permission) {
            $manager->managerPermissions()->create(['permission' => $permission, 'granted_by' => $owner->id]);
        }
    }
}

