<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScoreMustBelongToPlayer
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $player = $request->route('player');
        $score = $request->route('score');

        if ($player->scores->contains($score)) {
            return $next($request);
        }

        return response()->json(['message' => 'Score cannot be found or is not part of the group.'], 404);
    }
}
