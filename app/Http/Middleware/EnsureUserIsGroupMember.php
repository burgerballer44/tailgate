<?php

namespace App\Http\Middleware;

use App\Models\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Restricts routes to approved members of the routed group.
 */
class EnsureUserIsGroupMember
{
    /**
     * Validates that the authenticated user is an approved member for the routed group.
     *
     * The 403 response intentionally avoids disclosing whether the group or the
     * membership record exists to callers without access.
     *
     * @param  Request  $request  The current request, used to resolve the routed group and authenticated user.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @return Response The downstream response when the user is an approved member.
     *
     * @throws HttpException When the user is missing or is not an approved member.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $user = $request->user();

        $member = $group->members()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::APPROVED->value)
            ->first();

        if (! $member) {
            abort(403, 'You are not an approved member of this group.');
        }

        return $next($request);
    }
}
