<?php

namespace App\Http\Controllers;

use App\DTO\ValidatedMemberData;
use App\Exceptions\PredictionPolicyViolationException;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\GetGroupSeasonResultsRequest;
use App\Http\Requests\Group\JoinGroupRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupSeasonFollowsRequest;
use App\Http\Requests\Group\UpdateGroupPredictionScoringPolicyRequest;
use App\Http\Requests\Group\SubmitPredictionRequest;
use App\Http\Requests\Group\UpdateGroupPoliciesRequest;
use App\Http\Requests\Group\UpdatePredictionRequest;
use App\Http\Requests\Group\UserUpdateGroupRequest;
use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\Enums\GroupRole;
use App\Models\Enums\GroupThresholdRule;
use App\Models\Enums\InitialGroupLimitRule;
use App\Models\Member;
use App\Models\Enums\MemberStatus;
use App\Models\Player;
use App\Models\Prediction;
use App\Services\Contracts\GameQueryInterface;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use App\Services\Contracts\GroupSeasonLeaderboardServiceInterface;
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
use Illuminate\Support\Facades\Schema;

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
        private GroupSeasonLeaderboardServiceInterface $groupSeasonLeaderboardService,
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
        * invite code, checks current membership state, and either creates a new
        * pending membership or reactivates a previously removed membership.
     *
     * @param  JoinGroupRequest  $request  The validated request containing the invite code
     * @return RedirectResponse Redirects back with success/error messages
     */
    public function requestJoin(JoinGroupRequest $request): RedirectResponse
    {
        // Find the group by invite code.
        $group = $this->groupQueryService->findByInviteCode($request->invite_code);

        // If no group matches the invite code, stop early.
        if (! $group) {
            $this->setFlashAlert('error', 'Invalid invite code.');

            return redirect()->back();
        }

        // Reuse an existing membership when the user previously left the group.
        $existingMember = $group->members()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingMember instanceof Member) {
            if (in_array($existingMember->status, [MemberStatus::APPROVED->value, MemberStatus::PENDING->value], true)) {
                $this->setFlashAlert('error', 'You are already a member of this group.');

                return redirect()->back();
            }
        }

        // Check active capacity (approved + pending only).
        if ($this->groupQueryService->isGroupMemberLimitReached($group)) {
            $this->setFlashAlert('error', 'Group member limit reached.');

            return redirect()->back();
        }

        if ($existingMember instanceof Member) {
            // Rejoin flow: reuse historical membership record so past players/predictions stay linked.
            $existingMember->role = GroupRole::GROUP_MEMBER->value;
            $existingMember->status = MemberStatus::PENDING->value;

            if (Schema::hasColumn($existingMember->getTable(), 'left_at')) {
                $existingMember->left_at = null;
            }

            $existingMember->save();

            $this->setFlashAlert('success', 'Rejoin request submitted. Your previous players and predictions in this group were preserved.');

            return redirect()->route('dashboard');
        } else {
            // First-time join: create a new pending membership.
            $memberData = ValidatedMemberData::fromArray([
                'user_id' => $request->user()->id,
                'role' => GroupRole::GROUP_MEMBER,
                'status' => MemberStatus::PENDING,
            ]);

            $this->groupCommandService->addMember($group, $memberData);
        }

        // Show success message and redirect to dashboard.
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
        $validTabs = ['details', 'players', 'upcoming-games', 'leaderboard', 'raw-prediction-data'];

        $activeTab = in_array($request->query('tab'), $validTabs, true)
            ? $request->query('tab')
            : 'details';

        // Follow data is used for summary text and details tab rendering.
        $group->load(['follows.team']);
        $group->load(['seasonFollows.season']);

        $resultsSeasonOptions = $this->buildResultsSeasonOptions($group);

        $requestedSeasonId = $request->query('season_id');
        $requestedSeasonId = is_numeric($requestedSeasonId) ? (int) $requestedSeasonId : null;

        $defaultSeasonId = $this->resolveDefaultResultsSeasonId($group, $resultsSeasonOptions);

        $selectedResultsSeasonId = $requestedSeasonId !== null && array_key_exists($requestedSeasonId, $resultsSeasonOptions)
            ? $requestedSeasonId
            : $defaultSeasonId;

        // Resolve the current signed-in approved member record once for all tabs.
        $currentMember = $this->memberQueryService->findApprovedMemberForGroupAndUser(
            $group,
            $user
        );

        $highlightPlayerIds = [];
        if (in_array($activeTab, ['leaderboard', 'raw-prediction-data'], true)) {
            $highlightPlayerIds = $this->playerQueryService->getAllForMember($currentMember)
                ->pluck('id')
                ->map(static fn ($playerId): int => (int) $playerId)
                ->values()
                ->all();
        }

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
            'resultsSeasonOptions' => $resultsSeasonOptions,
            'selectedResultsSeasonId' => $selectedResultsSeasonId,
            'currentMember' => $currentMember,
            'memberPlayers' => $memberPlayers,
            'playerCount' => $memberPlayers->count(),
            'upcomingGames' => $upcomingGames,
            'predictionLookup' => $predictionLookup,
            'highlightPlayerIds' => $highlightPlayerIds,
            'regularMemberPlayerLimit' => InitialGroupLimitRule::MEMBER_PLAYER_LIMIT->value(),
            'availableGroupPolicies' => $this->policyEvaluator->groupRules(),
        ]);
    }

    /**
     * Return season-scoped leaderboard and raw prediction results payload.
     */
    public function seasonResults(GetGroupSeasonResultsRequest $request, Group $group): JsonResponse
    {
        $seasonId = (int) $request->input('season_id');
        $asOfGameId = $request->filled('as_of_game_id')
            ? (int) $request->input('as_of_game_id')
            : null;

        $results = $this->groupSeasonLeaderboardService->buildSeasonResults(
            groupId: $group->id,
            seasonId: $seasonId,
            asOfGameId: $asOfGameId,
        );

        return response()->json([
            'data' => $results->toArray(),
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
     * Build season-selector options ordered for the results experience.
     *
     * @return array<int, string>
     */
    private function buildResultsSeasonOptions(Group $group): array
    {
        if ($group->seasonFollows->isEmpty()) {
            return [];
        }

        $seasonMetrics = $this->resultsSeasonMetrics($group);

        return $group->seasonFollows
            ->sort(function ($left, $right) use ($seasonMetrics): int {
                $leftMetrics = $seasonMetrics[$left->season_id] ?? [
                    'is_active' => false,
                    'latest_game_timestamp' => null,
                    'latest_scorable_game_timestamp' => null,
                ];
                $rightMetrics = $seasonMetrics[$right->season_id] ?? [
                    'is_active' => false,
                    'latest_game_timestamp' => null,
                    'latest_scorable_game_timestamp' => null,
                ];

                if ($leftMetrics['is_active'] !== $rightMetrics['is_active']) {
                    return $leftMetrics['is_active'] ? -1 : 1;
                }

                if ($leftMetrics['latest_game_timestamp'] !== $rightMetrics['latest_game_timestamp']) {
                    return ($rightMetrics['latest_game_timestamp'] ?? PHP_INT_MIN) <=> ($leftMetrics['latest_game_timestamp'] ?? PHP_INT_MIN);
                }

                if ($leftMetrics['latest_scorable_game_timestamp'] !== $rightMetrics['latest_scorable_game_timestamp']) {
                    return ($rightMetrics['latest_scorable_game_timestamp'] ?? PHP_INT_MIN) <=> ($leftMetrics['latest_scorable_game_timestamp'] ?? PHP_INT_MIN);
                }

                return strcasecmp((string) $left->season?->name, (string) $right->season?->name);
            })
            ->mapWithKeys(fn ($seasonFollow): array => [$seasonFollow->season_id => (string) ($seasonFollow->season?->name ?? 'Season #'.$seasonFollow->season_id)])
            ->toArray();
    }

    /**
     * Resolve the default selected results season.
     */
    private function resolveDefaultResultsSeasonId(Group $group, array $resultsSeasonOptions): ?int
    {
        if ($resultsSeasonOptions === []) {
            return null;
        }

        $seasonMetrics = $this->resultsSeasonMetrics($group);

        foreach (array_keys($resultsSeasonOptions) as $seasonId) {
            if (($seasonMetrics[(int) $seasonId]['is_active'] ?? false) === true) {
                return (int) $seasonId;
            }
        }

        foreach (array_keys($resultsSeasonOptions) as $seasonId) {
            if (($seasonMetrics[(int) $seasonId]['latest_scorable_game_timestamp'] ?? null) !== null) {
                return (int) $seasonId;
            }
        }

        return (int) array_key_first($resultsSeasonOptions);
    }

    /**
     * Resolve per-season ordering metrics used by the results selector.
     *
     * @return array<int, array{is_active: bool, latest_game_timestamp: int|null, latest_scorable_game_timestamp: int|null}>
     */
    private function resultsSeasonMetrics(Group $group): array
    {
        $seasonIds = $group->seasonFollows->pluck('season_id')->all();

        if ($seasonIds === [] || $group->follows->isEmpty()) {
            return $group->seasonFollows
                ->mapWithKeys(fn ($seasonFollow): array => [
                    $seasonFollow->season_id => [
                        'is_active' => (bool) $seasonFollow->season?->active,
                        'latest_game_timestamp' => null,
                        'latest_scorable_game_timestamp' => null,
                    ],
                ])->all();
        }

        $followedTeamIds = $group->follows->pluck('team_id')->all();

        $games = Game::query()
            ->whereIn('season_id', $seasonIds)
            ->where(function ($query) use ($followedTeamIds): void {
                $query->whereIn('home_team_id', $followedTeamIds)
                    ->orWhereIn('away_team_id', $followedTeamIds);
            })
            ->get(['season_id', 'start_date_time', 'home_team_score', 'away_team_score']);

        $metrics = $group->seasonFollows
            ->mapWithKeys(fn ($seasonFollow): array => [
                $seasonFollow->season_id => [
                    'is_active' => (bool) $seasonFollow->season?->active,
                    'latest_game_timestamp' => null,
                    'latest_scorable_game_timestamp' => null,
                ],
            ])->all();

        foreach ($games as $game) {
            $latestGameTimestamp = $game->start_date_time ? strtotime((string) $game->start_date_time) : null;
            $isScorable = is_numeric($game->getRawOriginal('home_team_score')) && is_numeric($game->getRawOriginal('away_team_score'));

            if ($latestGameTimestamp !== null && $latestGameTimestamp !== false) {
                $metrics[$game->season_id]['latest_game_timestamp'] = max(
                    $metrics[$game->season_id]['latest_game_timestamp'] ?? PHP_INT_MIN,
                    $latestGameTimestamp,
                );

                if ($isScorable) {
                    $metrics[$game->season_id]['latest_scorable_game_timestamp'] = max(
                        $metrics[$game->season_id]['latest_scorable_game_timestamp'] ?? PHP_INT_MIN,
                        $latestGameTimestamp,
                    );
                }
            }
        }

        return $metrics;
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

        $this->memberCommandService->reject($member);

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

        $this->memberCommandService->remove($member);

        $this->setFlashAlert('success', 'Member removed from group. Their historical players and predictions were preserved.');

        return redirect()->back();
    }

    /**
     * Promote an approved member to group admin.
     */
    public function promoteMember(Group $group, Member $member): RedirectResponse
    {
        if ($member->user_id === $group->owner_id) {
            abort(403, 'The group owner already has administrative access.');
        }

        if ($member->role === GroupRole::GROUP_ADMIN->value) {
            $this->setFlashAlert('success', 'Member is already an admin.');

            return redirect()->back();
        }

        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $member->user_id,
            'role' => GroupRole::GROUP_ADMIN,
            'status' => MemberStatus::from($member->status),
        ]);

        $this->memberCommandService->update($member, $memberData);

        $this->setFlashAlert('success', 'Member promoted to admin.');

        return redirect()->back();
    }

    /**
     * Demote an approved admin member to regular member.
     */
    public function demoteMember(Group $group, Member $member): RedirectResponse
    {
        if ($member->user_id === $group->owner_id) {
            abort(403, 'The group owner role cannot be changed.');
        }

        if ($member->role === GroupRole::GROUP_MEMBER->value) {
            $this->setFlashAlert('success', 'Member is already a regular member.');

            return redirect()->back();
        }

        // Keep at least one admin assigned to preserve group governance.
        if (
            $group->admin->count() == GroupThresholdRule::MIN_NUMBER_ADMINS->value()
            && $group->admin->first()?->id === $member->id
        ) {
            abort(422, 'Group admin minimum reached. Promote another member to admin before demoting this admin.');
        }

        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $member->user_id,
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::from($member->status),
        ]);

        $this->memberCommandService->update($member, $memberData);

        $this->setFlashAlert('success', 'Admin changed to regular member.');

        return redirect()->back();
    }

    /**
     * Allow an approved member to leave the group without deleting historical contributions.
     */
    public function leaveGroup(Request $request, Group $group): RedirectResponse
    {
        $member = $this->memberQueryService->findApprovedMemberForGroupAndUser($group, $request->user());

        if ($member->user_id === $group->owner_id) {
            abort(403, 'Group owners cannot leave their own group. Transfer ownership first.');
        }

        $this->memberCommandService->leave($member);

        $this->setFlashAlert('success', 'You left the group. Your players and prediction history in this group were preserved.');

        return redirect()->route('dashboard');
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
