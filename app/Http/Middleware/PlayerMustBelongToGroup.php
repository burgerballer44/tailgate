<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerMustBelongToGroup
{
   /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return Response
    */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $player = $request->route('player');

        if ($group->players->contains($player)) {
            return $next($request);
        }

        return response()->json(['message' => 'Player cannot be found or is not part of the group.'], 404);
    }
}
