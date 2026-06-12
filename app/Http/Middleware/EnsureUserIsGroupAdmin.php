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
