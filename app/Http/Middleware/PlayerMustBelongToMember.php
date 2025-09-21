<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerMustBelongToMember
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');
        $member = $request->route('member');
        $player = $request->route('player');

        if ($member->players->contains($player)) {
            return $next($request);
        }

        return response()->json(['message' => 'Player cannot be found or is not part of the group.'], 404);
    }
}
