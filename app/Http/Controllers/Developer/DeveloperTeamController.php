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
    /**
     * Build the developer team controller with write, read, and import services.
     *
     * @param  TeamCommandInterface  $teamCommandService  Service responsible for team create, update, and delete actions.
     * @param  TeamQueryInterface  $teamQueryService  Service used to query teams for index and filters.
     * @param  TeamImportManagerInterface  $teamImportManager  Service orchestrating external team imports.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private TeamCommandInterface $teamCommandService,
        private TeamQueryInterface $teamQueryService,
        private TeamImportManagerInterface $teamImportManager,
    ) {}

    /**
     * Display a paginated list of teams with sport and type filter options.
     *
     * @param  Request  $request  Incoming request that can include team search filters.
     * @return View Renders the developer team index.
     */
    public function index(Request $request): View
    {
        return view('developer.teams.index', [
            'teams' => $this->teamQueryService->query($request->all())->paginate(),
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    /**
     * Show the form for creating a team.
     *
     * @return View Renders the developer team create form.
     */
    public function create()
    {
        return view('developer.teams.create', [
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    /**
     * Persist a new team from validated payload data.
     *
     * @param  StoreTeamRequest  $request  Validated request containing team attributes.
     * @return RedirectResponse Redirects to the team index after a successful create.
     */
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->teamCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'Team created successfully!');

        return redirect()->route('developer.teams.index');
    }

    /**
     * Display a single team record.
     *
     * @param  Team  $team  Route-bound team being viewed.
     * @return View Renders the developer team detail page.
     */
    public function show(Team $team): View
    {
        return view('developer.teams.show', ['team' => $team]);
    }

    /**
     * Show the form for editing a team.
     *
     * @param  Team  $team  Route-bound team being edited.
     * @return View Renders the developer team edit form.
     */
    public function edit(Team $team): View
    {
        return view('developer.teams.edit', [
            'team' => $team,
            'sports' => collect(Sport::cases())->pluck('value'),
            'types' => collect(TeamType::cases())->pluck('value'),
        ]);
    }

    /**
     * Update an existing team.
     *
     * @param  UpdateTeamRequest  $request  Validated request containing updated team attributes.
     * @param  Team  $team  Route-bound team that will be updated.
     * @return RedirectResponse Redirects to the team index after a successful update.
     */
    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->teamCommandService->update($team, $request->toDTO());

        $this->setFlashAlert('success', 'Team updated successfully!');

        return redirect()->route('developer.teams.index');
    }

    /**
     * Delete a team.
     *
     * @param  Team  $team  Route-bound team to delete.
     * @return RedirectResponse Redirects to the team index after deletion.
     */
    public function destroy(Team $team): RedirectResponse
    {
        $this->teamCommandService->delete($team);

        $this->setFlashAlert('success', 'Team deleted successfully!');

        return redirect()->route('developer.teams.index');
    }

    /**
     * Display the team import form with available external providers.
     *
     * @return View Renders the import screen used to choose an import source.
     */
    public function importTeams(): View
    {
        return view('developer.teams.import-teams', [
            'sources' => $this->teamImportManager->availableSources(),
        ]);
    }

    /**
     * Import teams from the selected external source.
     *
     * @param  ImportTeamsRequest  $request  Validated request that defines source and import options.
     * @return RedirectResponse Redirects to either the import form (when failed) or team index (when import completes).
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
