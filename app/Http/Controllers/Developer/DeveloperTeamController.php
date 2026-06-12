<?php

namespace App\Http\Controllers\Developer;

use App\Exceptions\TeamImportException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\ImportTeamsRequest;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamImportManagerInterface;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class DeveloperTeamController extends Controller
{
    public function __construct(
        private TeamCommandInterface $teamCommandService,
        private TeamQueryInterface $teamQueryService,
        private TeamImportManagerInterface $teamImportManager,
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

    /**
     * Displays the team import form, providing available import sources and seasons for the user to select from.
     */
    public function importTeams(): View
    {
        return view('developer.teams.import-teams', [
            'sources' => $this->teamImportManager->availableSources(),
        ]);
    }

    /**
     * Handles the submission of the team import form, performing the import operation
     * and redirecting back to the team index with appropriate flash messages.
     *
     * @param  ImportTeamsRequest  $request  The validated request containing the team import data.
     * @return RedirectResponse A redirect response back to the team index page with flash messages indicating the result of the import operation.
     */
    public function storeImportedTeams(ImportTeamsRequest $request): RedirectResponse
    {
        try {
            $result = $this->teamImportManager->import($request->toDTO());
        } catch (TeamImportException $exception) {
            $this->setFlashAlert('error', $exception->getMessage());

            return redirect()->route('developer.teams.import-teams')->withInput();
        } catch (Throwable $exception) {
            report($exception);

            $this->setFlashAlert('error', 'Team import failed due to an unexpected error.');

            return redirect()->route('developer.teams.import-teams')->withInput();
        }

        $hasChanges = $result->hasImports() || $result->hasUpdates();
        $summaryParts = [];

        if ($result->hasImports()) {
            $summaryParts[] = "Imported {$result->importedCount} team(s)";
        }

        if ($result->hasUpdates()) {
            $summaryParts[] = "Updated {$result->updatedCount} existing team(s)";
        }

        $summary = $summaryParts === []
            ? "No teams were imported or updated from {$result->sourceLabel}."
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
            $this->setFlashAlert('error', $result->hasErrors() ? $result->errors : 'No teams were imported or updated.');
        }

        return redirect()->route('developer.teams.index');
    }
}
