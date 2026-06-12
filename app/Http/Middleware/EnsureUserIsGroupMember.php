<?php

namespace App\Http\Middleware;

use App\Models\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts routes to approved members of the routed group.
 */
class EnsureUserIsGroupMember
{
    /**
     * Validates that the authenticated user is an approved member for the routed group.
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
