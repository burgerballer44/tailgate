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

    public function show(Season $season): View
    {
        $games = $season->games()->with('homeTeam', 'awayTeam')->paginate();

        return view('developer.seasons.show', ['season' => $season, 'games' => $games]);
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
     * @param Season $season The season to import games into.
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

        if ($result->isPartial()) {
            $this->setFlashAlert(
                'warning',
                array_merge([
                    "Imported {$result->importedCount} game(s) from {$result->sourceLabel}.",
                ], $result->errors)
            );
        } elseif ($result->hasImports()) {
            $this->setFlashAlert('success', "Imported {$result->importedCount} game(s) from {$result->sourceLabel}.");
        } else {
            $this->setFlashAlert('error', $result->hasErrors() ? $result->errors : 'No games were imported.');
        }

        return redirect()->route('developer.seasons.show', $season);
    }
}
