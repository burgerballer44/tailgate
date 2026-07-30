<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Return a temporary launch page when coming soon mode is enabled.
 */
final class AppComingSoon
{
    private const BYPASS_SESSION_KEY = 'coming_soon_bypass_granted';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.coming_soon')) {
            return $next($request);
        }

        if ($request->routeIs('alpha.*')) {
            return $next($request);
        }

        if ($request->hasSession() && $request->session()->get(self::BYPASS_SESSION_KEY, false) === true) {
            return $next($request);
        }

        return response()->view('coming-soon', status: 503);
    }
}
