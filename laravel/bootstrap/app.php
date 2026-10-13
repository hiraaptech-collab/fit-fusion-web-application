<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class, 'ai-access' => \App\Http\Middleware\AiAccess::class]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->web(append: [AuthenticateSession::class, EnsureAccountIsActive::class]);
        $middleware->trustHosts(at: function () {
            $host=parse_url(config('app.url'),PHP_URL_HOST) ?: 'localhost';
            $allowed=['^'.preg_quote($host,'#').'$'];
            if(app()->environment('local','testing'))$allowed=array_merge($allowed,['^localhost$','^127\.0\.0\.1$']);
            return $allowed;
        },subdomains:false);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/account');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never log database SQL/bindings or credentials during connection failures.
        $exceptions->report(function (\Illuminate\Database\QueryException $exception) {
            \Illuminate\Support\Facades\Log::error('Database operation failed.', ['sqlstate'=>(string)$exception->getCode()]);
            return false;
        });
        $exceptions->render(function (\Illuminate\Database\QueryException $exception, Request $request) {
            $body=['error'=>'The gym service is temporarily unavailable. Please try again later.','code'=>'DATABASE_UNAVAILABLE'];
            return $request->expectsJson()?response()->json($body,503,['Retry-After'=>'30']):response()->view('errors.503',[],503,['Retry-After'=>'30']);
        });
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (($request->expectsJson() || $request->is('api/*','client/ai')) && $exception->getStatusCode() === 419) {
                return response()->json([
                    'error' => 'Your session expired. Please refresh the page and try again.',
                    'code' => 'CSRF_TOKEN_MISMATCH',
                ], 419);
            }
        });
    })->create();


