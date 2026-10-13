<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Rules\PasswordLength;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function loginForm(): View
    {
        return view('auth.login');
    }

    public function registerForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'max:72', new PasswordLength(), Password::min(12)->mixedCase()->numbers()],
            'role' => ['prohibited'],
            'is_active' => ['prohibited'],
            'permissions' => ['prohibited'],
        ]);
        $user = DB::transaction(function () use ($data, $request) {
            $user = new User($data);
            $user->role = 'client';
            $user->is_active = true;
            $user->save();
            $user->clientProfile()->create([]);
            AuditLog::create(['user_id' => $user->id, 'action' => 'auth.register', 'ip_address' => $request->ip()]);
            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:72', new PasswordLength()],
            'remember' => ['sometimes', 'boolean'],
        ]);
        $key = 'login:'.hash('sha256', Str::lower($data['email']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many login attempts. Please wait '.RateLimiter::availableIn($key).' seconds.']);
        }
        if (! Auth::attempt([
            'email' => $data['email'], 'password' => $data['password'], 'is_active' => true,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These details are incorrect or the account is inactive.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'auth.login', 'ip_address' => $request->ip()]);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::create(['user_id' => $request->user()->id, 'action' => 'auth.logout', 'ip_address' => $request->ip()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('status', 'You have been signed out.');
    }

    public function account(): View
    {
        return view('auth.account');
    }

    private function normalizeEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
    }
}
