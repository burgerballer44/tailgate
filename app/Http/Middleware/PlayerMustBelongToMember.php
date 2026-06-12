<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts member-scoped routes to players owned by the current member.
 */
class PlayerMustBelongToMember
{
    /**
     * Validates that the routed player belongs to the routed member.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->route('member');
        $player = $request->route('player');

        if ($member->players->contains($player)) {
            return $next($request);
        }

        abort(404, 'Player cannot be found or is not part of the group.');
    }
}
