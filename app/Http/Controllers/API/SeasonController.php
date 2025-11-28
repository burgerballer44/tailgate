<?php

namespace App\Http\Controllers\API;

use App\Models\Game;
use App\Models\Team;
use App\Models\Season;
use App\Services\SeasonService;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Resources\SeasonResource;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Http\Requests\Season\UpdateSeasonRequest;

class SeasonController extends Controller
{
    public function __construct(
        private SeasonService $seasonService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SeasonResource::collection($this->seasonService->query(request()->input())->paginate(500));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSeasonRequest $request)
    {
        $season = $this->seasonService->create($request->toDTO());

        return new SeasonResource($season);
    }

    /**
     * Display the specified resource.
     */
    public function show(Season $season)
    {
        $season->games;

        return new SeasonResource($season);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSeasonRequest $request, Season $season)
    {
        $this->seasonService->update($season, $request->toDTO());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Season $season)
    {
        $this->seasonService->delete($season);

        return response()->json([], 202);
    }

    /**
     * Display a listing of the teams.
     */
    public function getTeams(Season $season)
    {
        // show ids in this case since we need them for adding a game
        return TeamResource::collection(Team::filter(['sport' => $season->sport])->get()->makeVisible(['id']));
    }
}
