<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts follow-scoped routes to follows that belong to the current group.
 */
class FollowBelongsToGroup
{
    /**
     * Validates that the routed follow belongs to the routed group.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $follow = $request->route('follow');

        if (! $group || ! $follow) {
            abort(404, 'Follow cannot be found or is not part of the group.');
        }

        if (! $group->follows()->whereKey($follow->id)->exists()) {
            abort(404, 'Follow cannot be found or is not part of the group.');
        }

        return $next($request);
    }
}
