<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Restricts season-scoped routes to games that belong to the current season.
 */
class GameMustBelongToSeason
{
    /**
     * Validates that the routed game is part of the routed season.
     *
     * The membership check is done against the season's loaded games collection,
     * which keeps the guard simple but assumes the route binding has already
     * resolved both models.
     *
     * @param  Request  $request  The current request, used to resolve the routed season and game.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @return Response The downstream response when the game belongs to the season.
     *
     * @throws HttpException When the game is missing or not part of the routed season.
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
