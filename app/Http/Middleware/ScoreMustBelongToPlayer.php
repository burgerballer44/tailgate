<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts player-scoped routes to scores owned by the current player.
 */
class ScoreMustBelongToPlayer
{
    /**
     * Validates that the routed score belongs to the routed player.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $player = $request->route('player');
        $score = $request->route('score');

        if ($player->scores->contains($score)) {
            return $next($request);
        }

        abort(404, 'Score cannot be found or is not part of the group.');
    }
}
