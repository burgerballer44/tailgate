<?php

namespace App\Http\Middleware;

use App\Models\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsGroupMember
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the authenticated user is an approved member of the group.
     * If not, aborts with a 403 error.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $user = $request->user();

        $member = $group->members()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::APPROVED->value)
            ->first();

        if (!$member) {
            abort(403, 'You are not an approved member of this group.');
        }

        return $next($request);
    }
}