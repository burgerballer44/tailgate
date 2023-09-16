<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Season\AddGameRequest;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Http\Requests\Season\UpdateGameRequest;
use App\Http\Requests\Season\UpdateSeasonRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\SeasonResource;
use App\Models\Game;
use App\Models\Season;

class SeasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new SeasonResource(Season::filter(request()->input())->paginate(50));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSeasonRequest $request)
    {
        $validated = $request->validated();

        $season = new Season($validated);

        $season->save();

        return new SeasonResource($season);
    }

    /**
     * Display the specified resource.
     */
    public function show(Season $season)
    {
        return new SeasonResource($season);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSeasonRequest $request, Season $season)
    {
        $validated = $request->validated();

        $season->fill($validated);
        
        $season->save();

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Season $season)
    {
        $season->delete();
        return response()->json([], 202);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function addGame(AddGameRequest $request, Season $season)
    {
        $validated = $request->validated();

        $game = new Game($validated);

        $game = $season->games()->save($game);
        
        return new GameResource($game);
    }

    /**
     * Update a newly created resource in storage.
     */
    public function updateGame(UpdateGameRequest $request, Season $season, Game $game)
    {
        $validated = $request->validated();

        $game->fill($validated);

        $game->save();
        
        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyGame(Season $season, Game $game)
    {
        if ($season->games->contains($game)) {
            $game->delete();
            return response()->json([], 202);
        }

       abort(404, 'Game cannot be found or is not part of the listed season.');
    }
}
