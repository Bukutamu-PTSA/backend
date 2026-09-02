<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     * For API requests, return null to throw an exception instead of redirecting.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, don't redirect to login (which doesn't exist)
        // The auth:sanctum middleware will throw AuthenticationException instead,
        // which will be handled by the exception handler to return 401 JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        return route('login');
    }
}
