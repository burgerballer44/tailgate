<?php

namespace App\Http\Controllers\API;

use App\Models\Team;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Services\TeamService;

class TeamController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TeamResource::collection($this->teamService->query(request()->input())->paginate(500));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        $team = $this->teamService->create($request->toDTO());

        return new TeamResource($team);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        return new TeamResource($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        $this->teamService->update($team, $request->toDTO());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        $this->teamService->delete($team);

        return response()->json([], 202);
    }
}
