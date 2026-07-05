<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Enforces role-based access for routes guarded by a required role value.
 */
class CheckRole
{
    /**
     * Validates authentication and role membership before continuing the request.
     *
     * The middleware redirects guests to the login page, then aborts with 403
     * for authenticated users whose role does not match the route constraint.
     *
     * @param  Request  $request  The current request, used to inspect the authenticated user.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @param  string  $role  The required role value for this route.
     * @return Response The downstream response for authorized users, or a redirect/403 response otherwise.
     *
     * @throws UrlGenerationException When the login route is not registered.
     * @throws HttpException When the authenticated user does not have the required role.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::user()->role !== $role) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
