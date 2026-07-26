<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Return a temporary launch page when coming soon mode is enabled.
 */
final class ShowComingSoonPage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.coming_soon')) {
            return $next($request);
        }

        return response()->view('coming-soon', status: 503);
    }
}