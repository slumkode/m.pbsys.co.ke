<?php

namespace App\Http\Middleware;

use App\Services\UserLoginActivityLogger;
use Closure;

class TrackAuthenticatedUserActivity
{
    protected $logger;

    public function __construct(UserLoginActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle($request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $this->logger->ensureActivity($request, $user);
        }

        $response = $next($request);

        $user = $request->user();

        if ($user) {
            $this->logger->recordPageVisit($request, $user);
        }

        return $response;
    }
}
