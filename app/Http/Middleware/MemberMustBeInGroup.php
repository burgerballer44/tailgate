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
