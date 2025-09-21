<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FollowBelongsToGroup
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $follow = $request->route('follow');

        if ($group->follow && $group->follow->is($follow)) {
            return $next($request);
        }

        return response()->json(['message' => 'Follow cannot be found or is not part of the group.'], 404);
    }
}
