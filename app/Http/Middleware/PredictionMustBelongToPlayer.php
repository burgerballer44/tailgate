<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Restricts player-scoped routes to predictions owned by the current player.
 */
class PredictionMustBelongToPlayer
{
    /**
     * Validates that the routed prediction belongs to the routed player.
     *
     * @param  Request  $request  The current request, used to resolve the routed player and prediction.
     * @param  Closure(Request): Response  $next  The next middleware or controller to execute.
     * @return Response The downstream response when the prediction belongs to the player.
     *
     * @throws HttpException When the player or prediction is missing or the prediction does not belong to the player.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $player = $request->route('player');
        $prediction = $request->route('prediction');

        if ($player->predictions->contains($prediction)) {
            return $next($request);
        }

        abort(404, 'Prediction cannot be found or is not part of the group.');
    }
}
