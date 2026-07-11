<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Models\Follow;
use App\Models\Group;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\Contracts\PredictionQueryInterface;
use App\Services\Contracts\SeasonQueryInterface;
use App\Services\Contracts\TeamQueryInterface;
use App\Services\Contracts\UserQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperGroupController extends Controller
{
    /**
     * Build the developer group controller with group command/query and policy services.
     *
     * @param  GroupCommandInterface  $groupCommandService  Service for group write operations.
     * @param  GroupQueryInterface  $groupQueryService  Service for group list and filter queries.
     * @param  PredictionPolicyEvaluatorInterface  $predictionPolicyEvaluator  Service for resolving enabled prediction policies.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private GroupCommandInterface $groupCommandService,
        private GroupQueryInterface $groupQueryService,
        private UserQueryInterface $userQueryService,
        private TeamQueryInterface $teamQueryService,
        private SeasonQueryInterface $seasonQueryService,
        private PredictionQueryInterface $predictionQueryService,
        private PredictionPolicyEvaluatorInterface $predictionPolicyEvaluator,
    ) {}

    /**
     * Display a paginated list of groups for the developer UI.
     *
     * @param  Request  $request  Incoming request with optional list filters.
     * @return View Renders the developer group index.
     */
    public function index(Request $request): View
    {
        return view('developer.groups.index', [
            'groups' => $this->groupQueryService->query($request->query())->paginate(),
            'users' => $this->userQueryService->query([])->get(),
        ]);
    }

    /**
     * Show the form for creating a group in the developer UI.
     *
     * @return View Renders the group create form with assignable users.
     */
    public function create(): View
    {
        return view('developer.groups.create', [
            'users' => $this->userQueryService->query([])->get(),
        ]);
    }

    /**
     * Persist a new group from validated request data.
     *
     * @param  StoreGroupRequest  $request  Validated request containing group fields.
     * @return RedirectResponse Redirects to the developer group index after creation.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->groupCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'Group created successfully!');

        return redirect()->route('developer.groups.index');
    }

    /**
     * Display a group's details using tab-specific data loading.
     *
     * This conditional loading keeps tab switches cheap and avoids eager loading
     * large prediction datasets when the user only needs summary information.
     *
     * @param  Request  $request  Incoming request that can specify the active details tab.
     * @param  Group  $group  Route-bound group being inspected.
     * @return View Renders the developer group detail page with tab-specific payload data.
     */
    public function show(Request $request, Group $group): View
    {
        // Whitelisting tabs prevents arbitrary relation loads from malformed query values.
        $validTabs = ['details', 'members', 'players', 'predictions'];

        $activeTab = in_array($request->query('tab'), $validTabs, true)
            ? $request->query('tab')
            : 'details';

        $enabledGroupRulesBySeason = [];

        if ($activeTab === 'details') {
            $group->load(['owner', 'follows.team', 'seasonFollows.season'])
                ->loadCount(['members', 'players']);

            foreach ($group->seasonFollows as $seasonFollow) {
                $enabledGroupRulesBySeason[] = [
                    'season_name' => $seasonFollow->season?->name ?? 'Season #'.$seasonFollow->season_id,
                    'rules' => $this->predictionPolicyEvaluator->enabledGroupRules($seasonFollow),
                ];
            }
        }

        if ($activeTab === 'members') {
            $group->load('members.user');
        }

        if ($activeTab === 'players') {
            $group->load('players.member.user');
        }

        $predictions = null;
        if ($activeTab === 'predictions') {
            $predictions = $this->predictionQueryService->getPredictionsForGroup($group)
                ->paginate(perPage: 20, pageName: 'predictions_page')
                ->appends(['tab' => 'predictions']);
        }

        return view('developer.groups.show', [
            'group' => $group,
            'predictions' => $predictions,
            'activeTab' => $activeTab,
            'enabledGroupRulesBySeason' => $enabledGroupRulesBySeason,
        ]);
    }

    /**
     * Show the form for editing an existing group.
     *
     * @param  Group  $group  Route-bound group being edited.
     * @return View Renders the developer group edit form.
     */
    public function edit(Group $group): View
    {
        return view('developer.groups.edit', [
            'group' => $group,
            'users' => $this->userQueryService->query([])->get(),
            'groupPolicies' => collect($this->predictionPolicyEvaluator->groupRules()),
        ]);
    }

    /**
     * Update an existing group.
     *
     * @param  UpdateGroupRequest  $request  Validated request containing updated group data.
     * @param  Group  $group  Route-bound group that will be updated.
     * @return RedirectResponse Redirects to the group detail page after update.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->groupCommandService->update($group, $request->toDTO());

        $this->setFlashAlert('success', 'Group updated successfully!');

        return redirect()->route('developer.groups.show', $group);
    }

    /**
     * Delete a group.
     *
     * @param  Group  $group  Route-bound group to delete.
     * @return RedirectResponse Redirects to the group index after deletion.
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->groupCommandService->delete($group);

        $this->setFlashAlert('success', 'Group deleted successfully!');

        return redirect()->route('developer.groups.index');
    }

    /**
     * Show the form used to follow a team from within a group.
     *
     * @param  Group  $group  Route-bound group that will own the follow.
     * @return View Renders the follow-team form with all teams.
     */
    public function createFollowTeam(Group $group): View
    {
        $teams = $this->teamQueryService->getAvailableTeamsForFollow();
        $availableSeasonsForFollow = $this->seasonQueryService->getAvailableSeasonsForFollow();

        return view('developer.groups.follow-team', [
            'group' => $group,
            'teams' => $teams,
            'availableSeasonsForFollow' => $availableSeasonsForFollow,
            'selectedSeasonIds' => $group->followedSeasonIds,
        ]);
    }

    /**
     * Attach a followed team to a group.
     *
     * @param  FollowTeamRequest  $request  Validated request identifying the team to follow.
     * @param  Group  $group  Route-bound group receiving the follow relationship.
     * @return RedirectResponse Redirects to the group detail page on success or back on failure.
     */
    public function followTeam(FollowTeamRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupCommandService->followTeam($group, $request->toDTO());

            $this->setFlashAlert('success', 'Team followed successfully!');

            return redirect()->route('developer.groups.show', $group);
        } catch (\Exception $e) {
            $this->setFlashAlert('error', $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Remove a followed team from a group.
     *
     * @param  Group  $group  Route-bound group that owns the follow.
     * @param  Follow  $follow  Route-bound follow relationship to remove.
     * @return RedirectResponse Redirects to the group detail page after removal.
     */
    public function removeFollow(Group $group, Follow $follow): RedirectResponse
    {
        $this->groupCommandService->removeFollow($group, $follow);

        $this->setFlashAlert('success', 'Follow removed successfully!');

        return redirect()->route('developer.groups.show', $group);
    }
}
