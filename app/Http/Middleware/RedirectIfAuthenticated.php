<?php

namespace App\Http\Middleware;

use App\Services\UserLoginActivityLogger;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            $user = Auth::guard($guard)->user();
            $logger = app(UserLoginActivityLogger::class);

            return redirect($logger->preferredRedirectUrl($user, $request, '/dashboard'));
        }

        return $next($request);
    }
}
