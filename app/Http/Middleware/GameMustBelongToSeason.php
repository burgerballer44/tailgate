<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts season-scoped routes to games that belong to the current season.
 */
class GameMustBelongToSeason
{
    /**
     * Validates that the routed game is part of the routed season.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $season = $request->route('season');
        $game = $request->route('game');

        if ($season->games->contains($game)) {
            return $next($request);
        }

        abort(404, 'Game cannot be found or is not part of the listed season.');
    }
}
