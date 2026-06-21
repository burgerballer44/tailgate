<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts member-scoped routes to members that belong to the current group.
 */
class MemberMustBeInGroup
{
    /**
     * Validates that the routed member belongs to the routed group.
     *
     * Returning 404 for both missing bindings and ownership mismatches avoids
     * leaking whether a member exists outside the current group.
     *
     * @param Request $request The current request, used to resolve the routed group and member.
     * @param Closure(Request): Response $next The next middleware or controller to execute.
     * @return Response The downstream response when the member belongs to the group.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException When the group or member is missing or the member does not belong to the group.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $member = $request->route('member');

        if (! $group || ! $member) {
            abort(404, 'Member cannot be found or is not part of the group.');
        }

        if (! $group->members()->whereKey($member->id)->exists()) {
            abort(404, 'Member cannot be found or is not part of the group.');
        }

        return $next($request);
    }
}
