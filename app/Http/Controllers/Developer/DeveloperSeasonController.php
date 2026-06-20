<?php

namespace App\Http\Controllers\Developer;

use App\Exceptions\GameImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Season\ImportSeasonGamesRequest;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Http\Requests\Season\UpdateSeasonRequest;
use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
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
    public function __construct(
        private SeasonCommandInterface $seasonCommandService,
        private SeasonQueryInterface $seasonQueryService,
        private GameImportManagerInterface $gameImportManager,
        private GameQueryInterface $gameQueryService,
    ) {}

    public function index(Request $request): View
    {
        return view('developer.seasons.index', [
            'seasons' => $this->seasonQueryService->query($request->all())->paginate(),
            'sports' => collect(Sport::cases())->pluck('value'),
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
        ]);
    }

    public function create()
    {
        return view('developer.seasons.create', [
            'sports' => collect(Sport::cases())->pluck('value'),
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
        ]);
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        $this->seasonCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'Season created successfully!');

        return redirect()->route('developer.seasons.index');
    }

    public function show(Request $request, Season $season): View
    {
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

    public function edit(Season $season): View
    {
        return view('developer.seasons.edit', [
            'season' => $season,
            'sports' => collect(Sport::cases())->pluck('value'),
            'seasonTypes' => collect(SeasonType::cases())->pluck('value'),
        ]);
    }

    public function update(UpdateSeasonRequest $request, Season $season): RedirectResponse
    {
        $this->seasonCommandService->update($season, $request->toDTO());

        $this->setFlashAlert('success', 'Season updated successfully!');

        return redirect()->route('developer.seasons.index');
    }

    public function destroy(Season $season): RedirectResponse
    {
        $this->seasonCommandService->delete($season);

        $this->setFlashAlert('success', 'Season deleted successfully!');

        return redirect()->route('developer.seasons.index');
    }

    /**
     * Shows the form for importing games into a season.
     *
     * @param  Season  $season  The season to import games into.
     * @return View The view for the import form.
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
