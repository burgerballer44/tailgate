<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents authenticated users from accessing guest-only routes.
 */
class RedirectIfAuthenticated
{
    /**
     * Redirects authenticated users away from guest routes to the dashboard.
     *
     * Empty guard lists fall back to Laravel's default guard so the middleware
     * continues to behave correctly when applied without explicit guard names.
     *
     * @param Request $request The current request, used for guard resolution and downstream handling.
     * @param Closure(Request): Response $next The next middleware or controller to execute.
     * @param string ...$guards Guard names to check in order before allowing the request to continue.
     * @return Response A redirect to the dashboard for authenticated users, or the downstream response for guests.
     * @throws \Illuminate\Routing\Exceptions\UrlGenerationException When the dashboard route is not registered.
     *
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect(route('dashboard'));
            }
        }

        return $next($request);
    }
}
