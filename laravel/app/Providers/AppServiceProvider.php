<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([\App\Models\AiConversation::class, \App\Models\FitnessProgress::class, \App\Models\Message::class] as $model) {
            Gate::policy($model, \App\Policies\ClientRecordPolicy::class);
        }
        Gate::policy(\App\Models\ClientReview::class, \App\Policies\ClientReviewPolicy::class);
        RateLimiter::for('feedback-submit', fn (Request $request) =>
            Limit::perMinute(5)->by('feedback-submit:'.$request->user()->id)
        );
        // Preserve the existing AI rate limit and response format.
        RateLimiter::for('fusion-ai', function (Request $request) {
            $ipLimit = Limit::perMinute(config('services.fusion_ai.requests_per_minute'))
                ->by('fusion-ai:'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'error' => 'Too many requests. Please wait a moment and try again.',
                        'code' => 'RATE_LIMITED',
                    ], 429, $headers);
                });
            if(!$request->user())return $ipLimit;
            return [$ipLimit, Limit::perMinute(config('services.fusion_ai.user_requests_per_minute'))->by('fusion-ai-user:'.$request->user()->id)];
        });

        foreach (['login' => 30, 'register' => 5, 'forgot-password' => 5, 'reset-password' => 10, 'manager-write' => 20] as $name => $limit) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($limit)->by($name.':'.$request->ip()));
        }

        Gate::before(function (User $user) {
            if (! $user->is_active || $user->trashed()) {
                return false;
            }
            return $user->role === 'owner' ? true : null;
        });
        Gate::define('create-managers', fn (User $user) => $user->role === 'owner');
        foreach (array_keys(config('gym.manager_permissions')) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasGymPermission($permission));
        }

        // Build reset links from APP_URL, not an untrusted HTTP Host header.
        ResetPassword::createUrlUsing(fn (User $user, string $token) =>
            rtrim(config('app.url'), '/').route('password.reset', ['token' => $token, 'email' => $user->email], false)
        );
    }
}


