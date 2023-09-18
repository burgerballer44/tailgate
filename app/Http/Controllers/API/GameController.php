<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Season\AddGameRequest;
use App\Http\Requests\Season\UpdateGameRequest;
use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Models\Season;

class GameController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(AddGameRequest $request, Season $season)
    {
        $validated = $request->validated();

        $game = new Game($validated);

        $game = $season->games()->save($game);
        
        return new GameResource($game);
    }

    /**
     * Update a newly created resource in storage.
     */
    public function update(UpdateGameRequest $request, Season $season, Game $game)
    {
        if (! $season->games->contains($game)) {
            abort(404, 'Game cannot be found or is not part of the listed season.');
        }

        $validated = $request->validated();

        $game->fill($validated);

        $game->save();
        
        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Season $season, Game $game)
    {
        if ($season->games->contains($game)) {
            $game->delete();
            return response()->json([], 202);
        }

       abort(404, 'Game cannot be found or is not part of the listed season.');
    }
}
