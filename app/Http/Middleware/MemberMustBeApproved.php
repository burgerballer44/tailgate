<?php

namespace App\Http\Middleware;

use App\Models\Enums\MemberStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Restricts member-scoped routes to approved memberships.
 */
class MemberMustBeApproved
{
    /**
     * Validates that the routed member exists and has approved status.
     *
     * @param  Request  $request  The current request, used to resolve the routed member.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @return Response The downstream response when the member is approved.
     *
     * @throws HttpException When the member is missing or not approved.
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
