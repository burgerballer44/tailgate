<?php

namespace App\Http\Middleware;

use App\Models\GroupRole;
use App\Models\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGroupAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user is an approved group admin.
     * If not, aborts with a 403 error.
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

        if (!$member) {
            abort(403, 'You must be an approved group admin to perform this action.');
        }

        return $next($request);
    }
}