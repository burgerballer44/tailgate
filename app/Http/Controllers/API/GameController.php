<?php

namespace App\Http\Controllers\API;

use App\Models\Game;
use App\Models\Season;
use App\Services\SeasonService;
use App\Http\Controllers\Controller;
use App\Http\Resources\GameResource;
use App\Http\Requests\Season\AddGameRequest;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Middleware\GameMustBelongToSeason;
use App\Http\Requests\Season\UpdateGameRequest;
use Illuminate\Routing\Controllers\HasMiddleware;

class GameController extends Controller implements HasMiddleware
{
    public function __construct(
        private SeasonService $seasonService
    ) {}

    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(GameMustBelongToSeason::class, only: ['update', 'destroy']),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddGameRequest $request, Season $season)
    {
        $game = $this->seasonService->addGame($season, $request->toDTO());

        return new GameResource($game);
    }

    /**
     * Update a newly created resource in storage.
     */
    public function update(UpdateGameRequest $request, Season $season, Game $game)
    {
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
        $game->delete();

        return response()->json([], 202);
    }
}
