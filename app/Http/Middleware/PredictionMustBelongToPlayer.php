<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts player-scoped routes to predictions owned by the current player.
 */
class PredictionMustBelongToPlayer
{
    /**
     * Validates that the routed prediction belongs to the routed player.
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