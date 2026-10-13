<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && (! $request->user()->is_active || $request->user()->trashed())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account is inactive.'], 403);
            }
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact the gym.']);
        }
        // Reload grants once per request so revocations apply immediately on the next request.
        if ($request->user()?->role === 'manager') $request->user()->load('managerPermissions');
        return $next($request);
    }
}

