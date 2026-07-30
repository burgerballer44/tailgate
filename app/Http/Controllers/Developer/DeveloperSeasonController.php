<?php

namespace App\Http\Controllers\Developer;

use App\Exceptions\GameImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Season\ImportSeasonGamesRequest;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Http\Requests\Season\UpdateSeasonRequest;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use App\Models\Season;
use App\Services\Contracts\GameImportManagerInterface;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\SeasonCommandInterface;
use App\Services\Contracts\SeasonQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class DeveloperSeasonController extends Controller
{
    /**
     * Build the developer season controller with season and game services.
     *
     * @param  SeasonCommandInterface  $seasonCommandService  Service for season create, update, and delete operations.
     * @param  SeasonQueryInterface  $seasonQueryService  Service for querying seasons for list views.
     * @param  GameImportManagerInterface  $gameImportManager  Service used to import games from external providers.
     * @param  GameQueryInterface  $gameQueryService  Service used to query season games and team options.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private SeasonCommandInterface $seasonCommandService,
        private SeasonQueryInterface $seasonQueryService,
        private GameImportManagerInterface $gameImportManager,
        private GameQueryInterface $gameQueryService,
    ) {}

    /**
     * Display a paginated list of seasons with available enum-backed filters.
     *
     * @param  Request  $request  Incoming request containing optional season filters.
     * @return View Renders the developer season index.
     */
    public function index(Request $request): View
    {
        return view('developer.seasons.index', [
            'seasons' => $this->seasonQueryService->query($request->all())->paginate(),
            'sports' => collect(Sport::cases())->pluck('value'),
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
        ]);
    }

    /**
     * Show the form for creating a season.
     *
     * @return View Renders the developer season create form.
     */
    public function create()
    {
        return view('developer.seasons.create', [
            'sports' => collect(Sport::cases())->pluck('value'),
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
        ]);
    }

    /**
     * Persist a new season from validated payload data.
     *
     * @param  StoreSeasonRequest  $request  Validated request containing season attributes.
     * @return RedirectResponse Redirects to the season index after successful creation.
     */
    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $this->seasonCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'Season created successfully!');

        return redirect()->route('developer.seasons.index');
    }

    /**
     * Show season details or games depending on the requested tab.
     *
     * @param  Request  $request  Incoming request that can include the active tab and game filters.
     * @param  Season  $season  Route-bound season being viewed.
     * @return View Renders the season detail page with tab-specific data.
     */
    public function show(Request $request, Season $season): View
    {
        // Restricting tabs here prevents accidental eager loads from arbitrary query values.
        $validTabs = ['details', 'games'];
        $activeTab = in_array($request->query('tab'), $validTabs, true)
            ? $request->query('tab')
            : 'details';

        $games = null;
        $teams = [];

        if ($activeTab === 'details') {
            $season->loadCount('games');
        }

        if ($activeTab === 'games') {
            $games = $this->gameQueryService
                ->query(array_merge($request->all(), ['season_id' => $season->id]))
                ->with('homeTeam', 'awayTeam')
                ->orderBy('start_date_time')
                ->paginate()
                ->withQueryString();

            $teams = $this->gameQueryService->getAvailableTeamsForSeason($season);
        }

        return view('developer.seasons.show', [
            'season' => $season,
            'games' => $games,
            'teams' => $teams,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Show the form for editing a season.
     *
     * @param  Season  $season  Route-bound season being edited.
     * @return View Renders the developer season edit form.
     */
    public function edit(Season $season): View
    {
        return view('developer.seasons.edit', [
            'season' => $season,
            'sports' => collect(Sport::cases())->pluck('value'),
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
        ]);
    }

    /**
     * Update an existing season.
     *
     * @param  UpdateSeasonRequest  $request  Validated request containing updated season attributes.
     * @param  Season  $season  Route-bound season that will be updated.
     * @return RedirectResponse Redirects to the season index after update.
     */
    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        $this->seasonCommandService->update($season, $request->toDTO());

        $this->setFlashAlert('success', 'Season updated successfully!');

        return redirect()->route('developer.seasons.index');
    }

    /**
     * Delete a season.
     *
     * @param  Season  $season  Route-bound season to delete.
     * @return RedirectResponse Redirects to the season index after deletion.
     */
    public function destroy(Season $season): RedirectResponse
    {
        $this->seasonCommandService->delete($season);

        $this->setFlashAlert('success', 'Season deleted successfully!');

        return redirect()->route('developer.seasons.index');
    }

    /**
     * Show the game import form for a specific season.
     *
     * @param  Season  $season  Route-bound season that receives imported games.
     * @return View Renders the import form with available source providers.
     */
    public function importGames(Season $season): View
    {
        return view('developer.seasons.import-games', [
            'season' => $season,
            'sources' => $this->gameImportManager->availableSources(),
            'seasonTypes' => collect(SeasonType::cases())
                ->mapWithKeys(fn (SeasonType $type) => [$type->value => $type->value])->all(),
        ]);
    }

    /**
     * Import games into a season and surface a summarized outcome message.
     *
     * @param  ImportSeasonGamesRequest  $request  Validated import request with provider and season metadata.
     * @param  Season  $season  Route-bound season receiving imported records.
     * @return RedirectResponse Redirects to either the import form (on failure) or season detail page (on completion).
     */
    public function storeImportedGames(ImportSeasonGamesRequest $request, Season $season): RedirectResponse
    {
        try {
            $result = $this->gameImportManager->import($season, $request->toDTO());
        } catch (GameImportException $exception) {
            $this->setFlashAlert('error', $exception->getMessage());

            return redirect()->route('developer.seasons.import-games', $season)->withInput();
        } catch (Throwable $exception) {
            report($exception);

            $this->setFlashAlert('error', 'Game import failed due to an unexpected error.');

            return redirect()->route('developer.seasons.import-games', $season)->withInput();
        }

        // We intentionally keep partial success visible because imports can include mixed valid/invalid rows.
        $hasChanges = $result->hasImports() || $result->hasUpdates();
        $summaryParts = [];

        if ($result->hasImports()) {
            $summaryParts[] = "Imported {$result->importedCount} game(s)";
        }

        if ($result->hasUpdates()) {
            $summaryParts[] = "Updated {$result->updatedCount} existing game(s)";
        }

        $summary = $summaryParts === []
            ? "No games were imported or updated from {$result->sourceLabel}."
            : implode('. ', $summaryParts)." from {$result->sourceLabel}.";

        if ($result->hasErrors() && $hasChanges) {
            $this->setFlashAlert(
                'warning',
                array_merge([
                    $summary,
                ], $result->errors)
            );
        } elseif ($hasChanges) {
            $this->setFlashAlert('success', $summary);
        } else {
            $this->setFlashAlert('error', $result->hasErrors() ? $result->errors : 'No games were imported or updated.');
        }

        return redirect()->route('developer.seasons.show', $season);
    }
}
