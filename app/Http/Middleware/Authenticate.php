<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Redirects unauthenticated browser requests to the login route while
 * preserving JSON responses for API callers.
 */
class Authenticate extends Middleware
{
    /**
     * Resolves the redirect destination for unauthenticated, non-JSON requests.
     *
     * Returning null keeps Laravel's default unauthenticated response path for
     * JSON requests so APIs receive a 401-style response instead of a redirect.
     *
     * @param Request $request The current request, used to detect whether the client expects JSON.
     * @return string|null The login URL for browser requests, or null when the request expects JSON.
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException When the login route is not registered.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
