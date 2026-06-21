<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Middleware\GameMustBelongToSeason;
use App\Http\Requests\Season\AddGameRequest;
use App\Http\Requests\Season\UpdateGameRequest;
use App\Models\Game;
use App\Models\HtmlEntity;
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
    /**
     * Register route middleware that ensures nested game routes cannot cross seasons.
     *
     * @return array<int, Middleware> Middleware definitions scoped to routes that accept both season and game bindings.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(GameMustBelongToSeason::class, only: ['show', 'edit', 'update', 'destroy']),
        ];
    }

    /**
     * Build the developer game controller with game and season command/query services.
     *
     * @param GameCommandInterface $gameCommandService Service for updating and deleting existing games.
     * @param GameQueryInterface $gameQueryService Service for listing games and resolving selectable teams.
     * @param SeasonCommandInterface $seasonCommandService Service for adding new games to a season.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private GameCommandInterface $gameCommandService,
        private GameQueryInterface $gameQueryService,
        private SeasonCommandInterface $seasonCommandService
    ) {}

    /**
     * Display games for a season in a paginated list.
     *
     * @param Season $season Route-bound season whose games are being listed.
     * @param Request $request Incoming request context for pagination and future filters.
     * @return View Renders the developer season games index.
     */
    public function index(Season $season, Request $request): View
    {
        return view('developer.games.index', [
            'season' => $season,
            'games' => $this->gameQueryService->query(['season_id' => $season->id])->paginate(),
        ]);
    }

    /**
     * Show the form for creating a game inside a specific season.
     *
     * @param Season $season Route-bound season that will own the new game.
     * @return View Renders the game create form with season-eligible teams.
     */
    public function create(Season $season): View
    {
        return view('developer.games.create', [
            'season' => $season,
            'teams' => $this->gameQueryService->getAvailableTeamsForSeason($season),
        ]);
    }

    /**
     * Store a new game under the selected season.
     *
     * @param Season $season Route-bound season receiving the new game.
     * @param AddGameRequest $request Validated request containing the game payload.
     * @return RedirectResponse Redirects back to the season page with the games tab selected.
     */
    public function store(Season $season, AddGameRequest $request): RedirectResponse
    {
        $game = $this->seasonCommandService->addGame($season, $request->toDTO());

        $this->setFlashAlert('success', 'Game created successfully!');

        return redirect()->route('developer.seasons.show', [
            'season' => $season,
            'tab' => 'games',
        ]);
    }

    /**
     * Display a single game that belongs to the current season.
     *
     * @param Season $season Route-bound season context for the nested game route.
     * @param Game $game Route-bound game record validated by middleware to belong to the season.
     * @return View Renders the game detail page.
     */
    public function show(Season $season, Game $game): View
    {
        return view('developer.games.show', [
            'season' => $season,
            'game' => $game,
            'startTimeTbdIndicator' => ($game->start_time_tbd ? HtmlEntity::QUESTION_MARK : HtmlEntity::CHECK_MARK)->character(),
        ]);
    }

    /**
     * Show the form for editing a game in the selected season.
     *
     * @param Season $season Route-bound season context for the nested game route.
     * @param Game $game Route-bound game being edited.
     * @return View Renders the game edit form with season-eligible teams.
     */
    public function edit(Season $season, Game $game): View
    {
        return view('developer.games.edit', [
            'season' => $season,
            'game' => $game,
            'teams' => $this->gameQueryService->getAvailableTeamsForSeason($season),
        ]);
    }

    /**
     * Update an existing game.
     *
     * @param Season $season Route-bound season context for post-update navigation.
     * @param Game $game Route-bound game being updated.
     * @param UpdateGameRequest $request Validated request containing updated game fields.
     * @return RedirectResponse Redirects back to the season page with the games tab selected.
     */
    public function update(Season $season, Game $game, UpdateGameRequest $request): RedirectResponse
    {
        $this->gameCommandService->update($game, $request->toDTO());

        $this->setFlashAlert('success', 'Game updated successfully!');

        return redirect()->route('developer.seasons.show', [
            'season' => $season,
            'tab' => 'games',
        ]);
    }

    /**
     * Delete a game from the selected season.
     *
     * @param Season $season Route-bound season context for post-delete navigation.
     * @param Game $game Route-bound game to delete.
     * @return RedirectResponse Redirects back to the season page with the games tab selected.
     */
    public function destroy(Season $season, Game $game): RedirectResponse
    {
        $this->gameCommandService->delete($game);

        $this->setFlashAlert('success', 'Game deleted successfully!');

        return redirect()->route('developer.seasons.show', [
            'season' => $season,
            'tab' => 'games',
        ]);
    }
}
