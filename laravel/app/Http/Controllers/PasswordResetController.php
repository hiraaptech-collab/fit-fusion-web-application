<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Rules\PasswordLength;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254']]);
        try {
            Password::sendResetLink(['email' => $data['email'], 'is_active' => true]);
        } catch (\Throwable $error) {
            // Do not log reset links, SMTP credentials, request bodies or exception messages.
            Log::warning('Password reset delivery failed.', ['type' => get_class($error)]);
        }
        // The same message avoids revealing whether an email address is registered.
        return back()->with('status', 'If an active account uses this email, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email', '')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $this->normalizeEmail($request);
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'confirmed', 'max:72', new PasswordLength(), PasswordRule::min(12)->mixedCase()->numbers()],
        ]);
        $status = Password::reset([...$data, 'is_active' => true], function (User $user, string $password) use ($request) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
            AuditLog::create(['user_id' => $user->id, 'action' => 'auth.password_reset', 'ip_address' => $request->ip()]);
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'This reset link is invalid or expired. Please request a new link.'])->withInput($request->only('email'));
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.');
    }

    private function normalizeEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        }
    }
}
