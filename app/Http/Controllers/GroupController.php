<?php

namespace App\Http\Controllers;

use App\DTO\ValidatedMemberData;
use App\Exceptions\PredictionPolicyViolationException;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\JoinGroupRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupSeasonFollowsRequest;
use App\Http\Requests\Group\UpdateGroupPredictionScoringPolicyRequest;
use App\Http\Requests\Group\SubmitPredictionRequest;
use App\Http\Requests\Group\UpdateGroupPoliciesRequest;
use App\Http\Requests\Group\UpdatePredictionRequest;
use App\Http\Requests\Group\UserUpdateGroupRequest;
use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\Player;
use App\Models\Prediction;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\MemberQueryInterface;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\Contracts\PlayerQueryInterface;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\Contracts\PredictionScoringPolicyCatalogInterface;
use App\Services\Contracts\PredictionQueryInterface;
use App\Services\Contracts\SeasonQueryInterface;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * GroupController handles user-facing group operations.
 *
 * This controller manages the creation of new groups and the process of joining
 * existing groups via invite codes. It serves regular users and focuses
 * on the social aspects of group formation and membership.
 */
class GroupController extends Controller
{
    /**
     * Create a new GroupController instance.
     *
     * We inject the GroupService to handle all group-related business logic,
     * keeping the controller focused on HTTP request/response handling.
     */
    public function __construct(
        private GroupCommandInterface $groupCommandService,
        private GroupQueryInterface $groupQueryService,
        private GameQueryInterface $gameQueryService,
        private MemberCommandInterface $memberCommandService,
        private MemberQueryInterface $memberQueryService,
        private PlayerCommandInterface $playerCommandService,
        private PlayerQueryInterface $playerQueryService,
        private PredictionQueryInterface $predictionQueryService,
        private PredictionPolicyEvaluatorInterface $policyEvaluator,
        private PredictionScoringPolicyCatalogInterface $predictionScoringPolicyCatalog,
        private SeasonQueryInterface $seasonQueryService,
        private TeamQueryInterface $teamQueryService,
    ) {}

    /**
     * Show the group creation form.
     *
     * This method displays the form where users can enter details to create
     * a new group. The form will collect the group name and handle setting
     * the owner automatically.
     *
     * @return View Returns the group creation view
     */
    public function create(): View
    {
        return view('groups.create');
    }

    /**
     * Store a newly created group.
     *
     * This method processes the group creation request. It uses the GroupService
     * to create the group with validated data, then redirects the user back to
     * their dashboard with a success message that includes the invite code for sharing.
     *
     * @param  StoreGroupRequest  $request  The validated request containing group data
     * @return RedirectResponse Redirects to dashboard with success message
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        // create the group
        $group = $this->groupCommandService->create($request->toDTO());

        // set a success flash message that includes the invite code for the user to share
        $this->setFlashAlert('success', 'Group created successfully! Invite code: '.$group->invite_code);

        // redirect back to dashboard so user can see their new group
        return redirect()->route('dashboard');
    }

    /**
     * Show the group join by invite code form.
     *
     * This method displays the form where users can enter an invite code
     * to request joining an existing group.
     *
     * @return View Returns the group joining view
     */
    public function join(): View
    {
        return view('groups.join');
    }

    /**
     * Process a request to join a group via invite code.
     *
     * This method handles the user's request to join a group. It validates the
     * invite code, checks for existing membership, and adds the user as a member
     * if everything is valid. Currently uses direct joining, but can be extended
     * with owner confirmation logic later.
     *
     * @param  JoinGroupRequest  $request  The validated request containing the invite code
     * @return RedirectResponse Redirects back with success/error messages
     */
    public function requestJoin(JoinGroupRequest $request): RedirectResponse
    {
        // find the group by invite code
        $group = $this->groupQueryService->findByInviteCode($request->invite_code);

        // if no group found with that code, show error and redirect back
        if (! $group) {
            $this->setFlashAlert('error', 'Invalid invite code.');

            return redirect()->back();
        }

        // check if the current user is already a member of this group
        // this prevents duplicate memberships
        if ($this->groupQueryService->isUserAlreadyMember($group, $request->user()->id)) {
            $this->setFlashAlert('error', 'You are already a member of this group.');

            return redirect()->back();
        }

        // check if the group has reached its member limit
        if ($this->groupQueryService->isGroupMemberLimitReached($group)) {
            $this->setFlashAlert('error', 'Group member limit reached.');

            return redirect()->back();
        }

        // add the user as a pending member of the group
        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $request->user()->id,
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::PENDING,
        ]);

        $this->groupCommandService->addMember($group, $memberData);

        // show success message and redirect to dashboard
        $this->setFlashAlert('success', 'Successfully joined the group!');

        return redirect()->route('dashboard');
    }

    /**
     * Display the specified group details.
     *
     * This method shows the group details page, which includes information about
     * the group, its members, and other related data.
     *
     * @param  Request  $request  The incoming request instance
     * @param  Group  $group  The group to display
     * @return View Returns the group details view
     */
    public function show(Request $request, Group $group): View
    {
        // get the authenticated user
        $user = $request->user();

        $validTabs = ['details', 'players', 'upcoming-games'];

        $activeTab = in_array($request->query('tab'), $validTabs, true)
            ? $request->query('tab')
            : 'details';

        // Follow data is used for summary text and details tab rendering.
        $group->load(['follows.team']);
        $group->load(['seasonFollows.season']);

        // Resolve the current signed-in approved member record once for all tabs.
        $currentMember = $this->memberQueryService->findApprovedMemberForGroupAndUser(
            $group,
            $user
        );

        if ($activeTab === 'details') {
            $group->load('owner')->loadCount('members');
        }

        $memberPlayers = new EloquentCollection;
        if (in_array($activeTab, ['players', 'upcoming-games'], true)) {
            $memberPlayers = $this->playerQueryService->getAllForMember($currentMember);
        }

        $upcomingGames = collect();
        $predictionLookup = [];
        if ($activeTab === 'upcoming-games') {
            $upcomingGames = $this->gameQueryService->getUpcomingGamesForGroup($group);

            if ($memberPlayers->isNotEmpty() && $upcomingGames->isNotEmpty()) {
                $predictions = $this->predictionQueryService->getPredictionsForPlayersAndGames($memberPlayers, $upcomingGames);

                foreach ($predictions as $prediction) {
                    $predictionLookup[$prediction->game_id.':'.$prediction->player_id] = [
                        'id' => $prediction->id,
                        'ulid' => $prediction->ulid,
                        'home_team_prediction' => $prediction->home_team_prediction,
                        'away_team_prediction' => $prediction->away_team_prediction,
                    ];
                }
            }
        }

        return view('groups.show', [
            'group' => $group,
            'activeTab' => $activeTab,
            'currentMember' => $currentMember,
            'memberPlayers' => $memberPlayers,
            'playerCount' => $memberPlayers->count(),
            'upcomingGames' => $upcomingGames,
            'predictionLookup' => $predictionLookup,
            'regularMemberPlayerLimit' => Group::REGULAR_MEMBER_PLAYER_LIMIT,
            'availableGroupPolicies' => $this->policyEvaluator->groupRules(),
        ]);
    }

    /**
     * Store a new prediction from the user-facing upcoming-games flow.
     */
    public function storePrediction(SubmitPredictionRequest $request, Group $group, Player $player): RedirectResponse|JsonResponse
    {
        $currentMember = $this->memberQueryService->findApprovedMemberForGroupAndUser(
            $group,
            $request->user()
        );

        if ($player->member_id !== $currentMember->id) {
            abort(403, 'You can only submit predictions for your own players.');
        }

        try {
            $prediction = $this->playerCommandService->submitPrediction($player, $request->toDTO());

            $this->setFlashAlert('success', 'Prediction submitted successfully!');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Prediction submitted successfully!',
                    'prediction' => [
                        'id' => $prediction->id,
                        'ulid' => $prediction->ulid,
                        'game_id' => $prediction->game_id,
                        'player_id' => $prediction->player_id,
                        'home_team_prediction' => $prediction->home_team_prediction,
                        'away_team_prediction' => $prediction->away_team_prediction,
                    ],
                ]);
            }
        } catch (PredictionPolicyViolationException $exception) {
            $this->setFlashAlert('error', $exception->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [
                        'prediction' => [$exception->getMessage()],
                    ],
                ], 422);
            }

            return redirect()
                ->route($this->predictionRedirectRouteName($request), $this->predictionRedirectRouteParameters($request, $group))
                ->withErrors(['prediction' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route($this->predictionRedirectRouteName($request), $this->predictionRedirectRouteParameters($request, $group));
    }

    /**
     * Update an existing prediction from the user-facing upcoming-games flow.
     */
    public function updatePrediction(UpdatePredictionRequest $request, Group $group, Player $player, Prediction $prediction): RedirectResponse|JsonResponse
    {
        $currentMember = $this->memberQueryService->findApprovedMemberForGroupAndUser(
            $group,
            $request->user()
        );

        if ($player->member_id !== $currentMember->id) {
            abort(403, 'You can only update predictions for your own players.');
        }

        if ($prediction->player_id !== $player->id) {
            abort(404, 'Prediction cannot be found for this player.');
        }

        try {
            $updatedPrediction = $this->playerCommandService->updatePrediction($prediction, $request->toDTO());

            $this->setFlashAlert('success', 'Prediction updated successfully!');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Prediction updated successfully!',
                    'prediction' => [
                        'id' => $updatedPrediction->id,
                        'ulid' => $updatedPrediction->ulid,
                        'game_id' => $updatedPrediction->game_id,
                        'player_id' => $updatedPrediction->player_id,
                        'home_team_prediction' => $updatedPrediction->home_team_prediction,
                        'away_team_prediction' => $updatedPrediction->away_team_prediction,
                    ],
                ]);
            }
        } catch (PredictionPolicyViolationException $exception) {
            $this->setFlashAlert('error', $exception->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [
                        'prediction' => [$exception->getMessage()],
                    ],
                ], 422);
            }

            return redirect()
                ->route($this->predictionRedirectRouteName($request), $this->predictionRedirectRouteParameters($request, $group))
                ->withErrors(['prediction' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route($this->predictionRedirectRouteName($request), $this->predictionRedirectRouteParameters($request, $group));
    }

    /**
     * Resolve the route name used after dashboard-aware prediction writes.
     */
    private function predictionRedirectRouteName(Request $request): string
    {
        return $this->redirectToDashboard($request)
            ? 'dashboard'
            : 'groups.show';
    }

    /**
     * Resolve route parameters used after dashboard-aware prediction writes.
     *
     * @return array<string, mixed>
     */
    private function predictionRedirectRouteParameters(Request $request, Group $group): array
    {
        if ($this->redirectToDashboard($request)) {
            return [];
        }

        return [
            'group' => $group,
            'tab' => 'upcoming-games',
        ];
    }

    /**
     * Determine whether prediction write originated from the dashboard quick-submit flow.
     */
    private function redirectToDashboard(Request $request): bool
    {
        return $request->input('redirect_to') === 'dashboard';
    }

    /**
     * Show the form for editing the specified group.
     *
     * This method displays the group management form where admins can
     * manage group settings, approve/reject join requests, and manage members.
     *
     * @param  Request  $request  The incoming request used to resolve member-selection context.
     * @param  Group  $group  The group to edit.
     * @return View Returns the group edit view.
     */
    public function edit(Request $request, Group $group): View
    {
        // get the authenticated user
        $user = $request->user();

        // load follows and season follows used by the manage page sections
        $group->load(['follows.team', 'seasonFollows.season']);

        // Get the approved members from the query service so the controller
        // stays focused on request flow and view composition.
        $approvedMembers = $this->memberQueryService->getApprovedMembersForGroup($group);

        // Member selection priority:
        // 1) explicit ?member=<ulid> from the member selector
        // 2) current admin's own member record
        // 3) first approved member as a safe fallback
        $selectedMemberUlid = $request->query('member');
        $selectedMemberUlid = is_string($selectedMemberUlid) ? $selectedMemberUlid : null;
        $currentAdminMember = $approvedMembers->firstWhere('user_id', $user->id);

        $selectedMember = $selectedMemberUlid
            ? $approvedMembers->firstWhere('ulid', $selectedMemberUlid)
            : $currentAdminMember;

        $selectedMember ??= $approvedMembers->first();

        $managedPlayers = null;
        if ($selectedMember) {
            $managedPlayers = $this->playerQueryService->getAllForMember($selectedMember);
        }

        return view('groups.edit', [
            'group' => $group,
            'approvedMembers' => $approvedMembers,
            'selectedMember' => $selectedMember,
            'managedPlayers' => $managedPlayers,
            'availableSeasonsForFollow' => $this->seasonQueryService->getAvailableSeasonsForFollow(),
            'selectedSeasonIds' => $group->followedSeasonIds,
            'availableGroupPolicies' => collect($this->policyEvaluator->groupRules()),
            'availablePredictionScoringPolicies' => $this->predictionScoringPolicyCatalog->options(),
            'defaultPredictionScoringPolicyKey' => $this->predictionScoringPolicyCatalog->defaultKey(),
        ]);
    }

    /**
     * Update the specified group.
     *
     * This method processes updates to group settings. Only owners and admins can update.
     *
     * @param  UserUpdateGroupRequest  $request  The validated update request
     * @param  Group  $group  The group to update
     * @return RedirectResponse Redirects back with success message
     */
    public function update(UserUpdateGroupRequest $request, Group $group): RedirectResponse
    {
        // the owner_id is required in the DTO for validation purposes
        $this->groupCommandService->update($group, $request->toDTO($group->owner_id));

        $this->setFlashAlert('success', 'Group updated successfully!');

        return $this->redirectToEditTab($request, $group, 'settings');
    }

    /**
     * Update optional prediction policies for one followed season.
     *
     * App-level policies remain always-on and are not configurable here.
     */
    public function updatePolicies(UpdateGroupPoliciesRequest $request, Group $group): RedirectResponse
    {
        $this->groupCommandService->updatePolicies($group, $request->toDTO());

        $this->setFlashAlert('success', 'Season prediction policies updated successfully!');

        return $this->redirectToEditTab($request, $group, 'seasons');
    }

    /**
     * Update the selected prediction scoring policy for one followed season.
     */
    public function updatePredictionScoringPolicy(UpdateGroupPredictionScoringPolicyRequest $request, Group $group): RedirectResponse
    {
        $this->groupCommandService->updatePredictionScoringPolicy($group, $request->toDTO());

        $this->setFlashAlert('success', 'Season prediction scoring policy updated successfully!');

        return $this->redirectToEditTab($request, $group, 'seasons');
    }

    /**
     * Update the explicit seasons followed by the group.
     */
    public function updateSeasonFollows(UpdateGroupSeasonFollowsRequest $request, Group $group): RedirectResponse
    {
        $this->groupCommandService->syncSeasonFollows($group, $request->toDTO());

        $this->setFlashAlert('success', 'Season follows updated successfully!');

        return $this->redirectToEditTab($request, $group, 'seasons');
    }

    /**
     * Resolve a safe edit tab key from the request.
     */
    private function requestedEditTab(Request $request, string $fallback): string
    {
        $tab = $request->input('tab');

        if (! is_string($tab)) {
            return $fallback;
        }

        return in_array($tab, ['settings', 'seasons', 'players', 'members'], true)
            ? $tab
            : $fallback;
    }

    /**
     * Redirect back to the group edit screen on the requested tab.
     */
    private function redirectToEditTab(Request $request, Group $group, string $fallbackTab): RedirectResponse
    {
        return redirect()->route('groups.edit', [
            'group' => $group,
            'tab' => $this->requestedEditTab($request, $fallbackTab),
        ]);
    }

    /**
     * Approve a pending member join request.
     *
     * This method approves a pending join request, changing the member's status to approved.
     * Only group owners and admins can approve requests.
     *
     * @param  Group  $group  The group
     * @param  Member  $member  The pending member to approve
     * @return RedirectResponse Redirects back with success message
     */
    public function approveMember(Group $group, Member $member): RedirectResponse
    {
        // ensure member is pending
        if ($member->status !== MemberStatus::PENDING->value) {
            abort(404, 'Invalid member or not pending.');
        }

        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $member->user_id,
            'role' => GroupRole::from($member->role),
            'status' => MemberStatus::APPROVED,
        ]);

        $this->memberCommandService->update($member, $memberData);

        $this->setFlashAlert('success', 'Member approved successfully!');

        return redirect()->back();
    }

    /**
     * Reject a pending member join request.
     *
     * This method rejects a pending join request, either by deleting the member
     * or changing status to rejected. Only group owners and admins can reject requests.
     *
     * @param  Group  $group  The group
     * @param  Member  $member  The pending member to reject
     * @return RedirectResponse Redirects back with success message
     */
    public function rejectMember(Group $group, Member $member): RedirectResponse
    {
        // ensure member is pending
        if ($member->status !== MemberStatus::PENDING->value) {
            abort(404, 'Invalid member or not pending.');
        }

        $this->memberCommandService->delete($member);

        $this->setFlashAlert('success', 'Join request rejected.');

        return redirect()->back();
    }

    /**
     * Remove an approved member from the group.
     *
     * This method removes an approved member from the group. Only group owners and admins can remove members.
     * Cannot remove the owner or the last admin.
     *
     * @param  Group  $group  The group
     * @param  Member  $member  The member to remove
     * @return RedirectResponse Redirects back with success message
     */
    public function removeMember(Group $group, Member $member): RedirectResponse
    {
        // cannot remove the owner
        if ($member->user_id === $group->owner_id) {
            abort(403, 'Cannot remove the group owner.');
        }

        $this->memberCommandService->delete($member);

        $this->setFlashAlert('success', 'Member removed from group.');

        return redirect()->back();
    }

    /**
     * Show the form for following a team.
     *
     * This method displays the form where group admins can choose a team to follow.
     *
     * @param  Group  $group  The group to follow a team for
     * @return View Returns the follow team view
     */
    public function createFollowTeam(Group $group): View
    {
        $teams = $this->teamQueryService->getAvailableTeamsForFollow();
        $availableSeasonsForFollow = $this->seasonQueryService->getAvailableSeasonsForFollow();

        return view('groups.follow-team', [
            'group' => $group,
            'teams' => $teams,
            'availableSeasonsForFollow' => $availableSeasonsForFollow,
            'selectedSeasonIds' => $group->followedSeasonIds,
        ]);
    }

    /**
     * Follow a team for the group.
     *
     * This method processes the follow team request and creates the follow relationship.
     *
     * @param  FollowTeamRequest  $request  The validated request containing team data
     * @param  Group  $group  The group to follow the team
     * @return RedirectResponse Redirects back with success/error messages
     */
    public function followTeam(FollowTeamRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupCommandService->followTeam($group, $request->toDTO());

            $this->setFlashAlert('success', 'Team followed successfully!');

            return redirect()->route('groups.show', $group);
        } catch (\Exception $e) {
            $this->setFlashAlert('error', $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Remove a follow relationship.
     *
     * This method removes the follow relationship for the group.
     *
     * @param  Group  $group  The group to unfollow
     * @param  Follow  $follow  The follow to remove
     * @return RedirectResponse Redirects back with success message
     */
    public function removeFollow(Group $group, Follow $follow): RedirectResponse
    {
        $this->groupCommandService->removeFollow($group, $follow);

        $this->setFlashAlert('success', 'Follow removed successfully!');

        return redirect()->route('groups.show', $group);
    }
}
