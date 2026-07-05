<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Restricts member-scoped routes to players owned by the current member.
 */
class PlayerMustBelongToMember
{
    /**
     * Validates that the routed player belongs to the routed member.
     *
     * @param  Request  $request  The current request, used to resolve the routed member and player.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @return Response The downstream response when the player belongs to the member.
     *
     * @throws HttpException When the member or player is missing or the player does not belong to the member.
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
