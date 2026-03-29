<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperTeamController extends Controller
{
    public function __construct(
        private TeamCommandInterface $teamCommandService,
        private TeamQueryInterface $teamQueryService
    ) {}

    public function index(Request $request): View
    {
        return view('developer.teams.index', [
            'teams' => $this->teamQueryService->query($request->all())->paginate(),
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    public function create()
    {
        return view('developer.teams.create', [
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->teamCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'Team created successfully!');

        return redirect()->route('developer.teams.index');
    }

    public function show(Team $team): View
    {
        return view('developer.teams.show', ['team' => $team]);
    }

    public function edit(Team $team): View
    {
        return view('developer.teams.edit', [
            'team' => $team,
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->teamCommandService->update($team, $request->toDTO());

        $this->setFlashAlert('success', 'Team updated successfully!');

        return redirect()->route('developer.teams.index');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->teamCommandService->delete($team);

        $this->setFlashAlert('success', 'Team deleted successfully!');

        return redirect()->route('developer.teams.index');
    }
}
