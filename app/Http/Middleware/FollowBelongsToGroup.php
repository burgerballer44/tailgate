<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Restricts follow-scoped routes to follows that belong to the current group.
 */
class FollowBelongsToGroup
{
    /**
     * Validates that the routed follow belongs to the routed group.
     *
     * Returning 404 for both missing bindings and mismatched ownership keeps the
     * route from exposing whether the follow exists outside the group.
     *
     * @param  Request  $request  The current request, used to resolve the routed group and follow.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @return Response The downstream response when the follow belongs to the group.
     *
     * @throws HttpException When the group or follow is missing or the follow does not belong to the group.
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
