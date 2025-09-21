<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GameMustBelongToSeason
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $season = $request->route('season');
        $game = $request->route('game');

        if ($season->games->contains($game)) {
            return $next($request);
        }

        return response()->json(['message' => 'Game cannot be found or is not part of the listed season.'], 404);
    }
}
