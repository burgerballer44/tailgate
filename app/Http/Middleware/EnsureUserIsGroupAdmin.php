<?php

namespace App\Http\Middleware;

use App\Models\GroupRole;
use App\Models\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts routes to approved group administrators.
 */
class EnsureUserIsGroupAdmin
{
    /**
     * Validates that the authenticated user is an approved admin for the routed group.
     *
     * This middleware depends on route-model binding for both the group and the
     * authenticated user, and returns 403 to avoid revealing membership details.
     *
     * @param Request $request The current request, used to resolve the routed group and authenticated user.
     * @param Closure(Request): Response $next The next middleware or controller to execute.
     * @return Response The downstream response when the user is an approved group admin.
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException When the user is missing or is not an approved group admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $user = $request->user();

        $member = $group->members()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::APPROVED->value)
            ->where('role', GroupRole::GROUP_ADMIN->value)
            ->first();

        if (! $member) {
            abort(403, 'You must be an approved group admin to perform this action.');
        }

        return $next($request);
    }
}
