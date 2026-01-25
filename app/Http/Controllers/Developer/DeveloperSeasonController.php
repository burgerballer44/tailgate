<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\Sport;
use App\Models\SeasonType;
use Illuminate\Http\Request;
use App\Services\SeasonService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Season\StoreSeasonRequest;
use App\Http\Requests\Season\UpdateSeasonRequest;

class DeveloperSeasonController extends Controller
{
    public function __construct(
        private SeasonService $seasonService
    ) {}

    public function index(Request $request): View
    {
        return view('developer.seasons.index', [
            'seasons' => $this->seasonService->query($request->all())->paginate(),
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
        $this->seasonService->create($request->toDTO());

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
        $this->seasonService->update($season, $request->toDTO());

        $this->setFlashAlert('success', 'Season updated successfully!');

        return redirect()->route('developer.seasons.index');
    }

    public function destroy(Season $season): RedirectResponse
    {
        $this->seasonService->delete($season);

        $this->setFlashAlert('success', 'Season deleted successfully!');

        return redirect()->route('developer.seasons.index');
    }
}
