<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Redirects unauthenticated browser requests to the login route.
 */
class Authenticate extends Middleware
{
    /**
     * Resolves the redirect destination for unauthenticated, non-JSON requests.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
