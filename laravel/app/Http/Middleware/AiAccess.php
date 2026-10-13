<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AiAccess
{
    public function handle(Request $request,Closure $next)
    {
        $user=$request->user();
        $allowed=$user&&$user->is_active&&!$user->trashed()&&in_array($user->role,['owner','manager','client'],true);
        $request->attributes->set('fusion_ai_provider_allowed',$allowed||config('services.fusion_ai.allow_guest_provider'));
        return $next($request);
    }
}
