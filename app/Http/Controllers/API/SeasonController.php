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
}
