<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Sport;
use App\Models\TeamType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Services\TeamService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;

class TeamController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {}

    public function index(Request $request): View
    {
        return view('admin.teams.index', [
            'teams' => $this->teamService->query($request->all())->paginate(),
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    public function create()
    {
        return view('admin.teams.create', [
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->teamService->create($request->toDTO());

        $this->setFlashAlert('success', 'Team created successfully!');

        return redirect()->route('admin.teams.index');
    }

    public function show(Team $team): View
    {
        return view('admin.teams.show', ['team' => $team]);
    }

    public function edit(Team $team): View
    {
        return view('admin.teams.edit', [
            'team' => $team,
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->teamService->update($team, $request->toDTO());

        $this->setFlashAlert('success', 'Team updated successfully!');

        return redirect()->route('admin.teams.index');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->teamService->delete($team);

        $this->setFlashAlert('success', 'Team deleted successfully!');

        return redirect()->route('admin.teams.index');
    }
}
