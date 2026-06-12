<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces role-based access for routes guarded by a required role value.
 */
class CheckRole
{
    /**
     * Validates authentication and role membership before continuing the request.
     *
     * @param  Closure(Request): (Response)  $next
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
