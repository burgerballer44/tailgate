<?php

namespace App\Http\Middleware;

use App\Models\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts member-scoped routes to approved memberships.
 */
class MemberMustBeApproved
{
    /**
     * Validates that the routed member exists and has approved status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->route('member');

        if (! $member || $member->status !== MemberStatus::APPROVED->value) {
            abort(404, 'Invalid member or not approved.');
        }

        return $next($request);
    }
}
