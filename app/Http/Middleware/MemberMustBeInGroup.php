<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MemberMustBeInGroup
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $member = $request->route('member');

        if ($group->members->contains($member)) {
            return $next($request);
        }

        return response()->json(['message' => 'Member cannot be found or is not part of the group.'], 404);
    }
}
