<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Middleware\GameMustBelongToSeason;
use App\Http\Requests\Season\AddGameRequest;
use App\Http\Requests\Season\UpdateGameRequest;
use App\Models\Game;
use App\Models\Season;
use App\Services\Contracts\GameCommandInterface;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\SeasonCommandInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DeveloperGameController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(GameMustBelongToSeason::class, only: ['show', 'edit', 'update', 'destroy']),
        ];
    }

    public function __construct(
        private GameCommandInterface $gameCommandService,
        private GameQueryInterface $gameQueryService,
        private SeasonCommandInterface $seasonCommandService
    ) {}

    public function index(Season $season, Request $request): View
    {
        return view('developer.games.index', [
            'season' => $season,
            'games' => $this->gameQueryService->query(['season_id' => $season->id])->paginate(),
        ]);
    }

    public function create(Season $season): View
    {
        return view('developer.games.create', [
            'season' => $season,
            'teams' => $this->gameQueryService->getAvailableTeamsForSeason($season),
        ]);
    }

    public function store(Season $season, AddGameRequest $request): RedirectResponse
    {
        $game = $this->seasonCommandService->addGame($season, $request->toDTO());

        $this->setFlashAlert('success', 'Game created successfully!');

        return redirect()->route('developer.seasons.games.index', $season);
    }

    public function show(Season $season, Game $game): View
    {
        return view('developer.games.show', [
            'season' => $season,
            'game' => $game,
        ]);
    }

    public function edit(Season $season, Game $game): View
    {
        return view('developer.games.edit', [
            'season' => $season,
            'game' => $game,
            'teams' => $this->gameQueryService->getAvailableTeamsForSeason($season),
        ]);
    }

    public function update(Season $season, Game $game, UpdateGameRequest $request): RedirectResponse
    {
        $this->gameCommandService->update($game, $request->toDTO());

        $this->setFlashAlert('success', 'Game updated successfully!');

        return redirect()->route('developer.seasons.games.index', $season);
    }

    public function destroy(Season $season, Game $game): RedirectResponse
    {
        $this->gameCommandService->delete($game);

        $this->setFlashAlert('success', 'Game deleted successfully!');

        return redirect()->route('developer.seasons.games.index', $season);
    }
}
