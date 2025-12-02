<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Season;
use Illuminate\Http\Request;
use App\Services\GameService;
use App\Services\SeasonService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Middleware\GameMustBelongToSeason;
use App\Http\Requests\Season\AddGameRequest;
use App\Http\Requests\Season\UpdateGameRequest;
use Illuminate\Routing\Controllers\HasMiddleware;

class GameController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(GameMustBelongToSeason::class, only: ['show', 'edit', 'update', 'destroy']),
        ];
    }

    public function __construct(
        private GameService $gameService,
        private SeasonService $seasonService
    ) {}

    public function index(Season $season, Request $request): View
    {
        return view('admin.games.index', [
            'season' => $season,
            'games' => $this->gameService->query(['season_id' => $season->id])->paginate(),
        ]);
    }

    public function create(Season $season): View
    {
        return view('admin.games.create', [
            'season' => $season,
            'teams' => $this->gameService->getAvailableTeamsForSeason($season),
        ]);
    }

    public function store(Season $season, AddGameRequest $request): RedirectResponse
    {
        $game = $this->seasonService->addGame($season, $request->toDTO());

        $this->setFlashAlert('success', 'Game created successfully!');

        return redirect()->route('seasons.games.index', $season);
    }

    public function show(Season $season, Game $game): View
    {
        return view('admin.games.show', [
            'season' => $season,
            'game' => $game,
        ]);
    }

    public function edit(Season $season, Game $game): View
    {
        return view('admin.games.edit', [
            'season' => $season,
            'game' => $game,
            'teams' => $this->gameService->getAvailableTeamsForSeason($season),
        ]);
    }

    public function update(Season $season, Game $game, UpdateGameRequest $request): RedirectResponse
    {
        $game = $this->gameService->update($game, $request->toDTO());

        $this->setFlashAlert('success', 'Game updated successfully!');

        return redirect()->route('seasons.games.index', $season);
    }

    public function destroy(Season $season, Game $game): RedirectResponse
    {
        $this->gameService->delete($game);

        $this->setFlashAlert('success', 'Game deleted successfully!');

        return redirect()->route('seasons.games.index', $season);
    }
}