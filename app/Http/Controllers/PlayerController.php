<?php

namespace App\Http\Controllers;

use App\Http\Requests\Group\StorePlayerRequest;
use App\Http\Requests\Group\UpdatePlayerRequest;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\Contracts\PlayerQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PlayerController handles user-facing player management.
 *
 * This controller allows users to create and manage their players within a group.
 * Players belong to members, and each member can have multiple players (up to a limit).
 */
class PlayerController extends Controller
{
    public function __construct(
        private PlayerCommandInterface $playerCommandService,
        private PlayerQueryInterface $playerQueryService,
    ) {}

    /**
     * Display a listing of players for a member.
     */
    public function index(Request $request, Group $group, Member $member): View
    {
        $this->authorizeMemberPlayerManagement($request, $group, $member, 'view players for your own membership.');

        [$returnRouteName, $returnRouteParams] = $this->getReturnRoute($request, $group, $member);

        return view('groups.players.index', [
            'group' => $group,
            'member' => $member,
            'players' => $member->players()->latest()->paginate(),
            'routeBaseName' => $this->getRouteBaseName($request),
            'returnRouteName' => $returnRouteName,
            'returnRouteParams' => $returnRouteParams,
            'effectivePlayerLimit' => $this->getEffectivePlayerLimit($request, $group),
        ]);
    }

    /**
     * Show the form to create a new player.
     */
    public function create(Request $request, Group $group, Member $member): View
    {
        $this->authorizeMemberPlayerManagement($request, $group, $member, 'create players for your own membership.');

        [$returnRouteName, $returnRouteParams] = $this->getReturnRoute($request, $group, $member);

        return view('groups.players.create', [
            'group' => $group,
            'member' => $member,
            'routeBaseName' => $this->getRouteBaseName($request),
            'returnRouteName' => $returnRouteName,
            'returnRouteParams' => $returnRouteParams,
            'effectivePlayerLimit' => $this->getEffectivePlayerLimit($request, $group),
        ]);
    }

    /**
     * Store a newly created player.
     */
    public function store(StorePlayerRequest $request, Group $group, Member $member): RedirectResponse
    {
        $this->authorizeMemberPlayerManagement($request, $group, $member, 'manage your own players.');

        try {
            // Create the player for this member
            $this->playerCommandService->createForMember($member, $request->toDTO());

            $this->setFlashAlert('success', 'Player created successfully!');

            [$returnRouteName, $returnRouteParams] = $this->getReturnRoute($request, $group, $member);

            return redirect()->route($returnRouteName, $returnRouteParams);
        } catch (\Exception $e) {
            $this->setFlashAlert('error', 'Failed to create player: '.$e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * Show the form to edit an existing player.
     */
    public function edit(Request $request, Group $group, Member $member, Player $player): View
    {
        $this->authorizeMemberPlayerManagement($request, $group, $member, 'edit your own players.');
        $this->ensurePlayerBelongsToMember($player, $member);

        [$returnRouteName, $returnRouteParams] = $this->getReturnRoute($request, $group, $member);

        return view('groups.players.edit', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'routeBaseName' => $this->getRouteBaseName($request),
            'returnRouteName' => $returnRouteName,
            'returnRouteParams' => $returnRouteParams,
        ]);
    }

    /**
     * Update an existing player.
     */
    public function update(UpdatePlayerRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->authorizeMemberPlayerManagement($request, $group, $member, 'manage your own players.');
        $this->ensurePlayerBelongsToMember($player, $member);

        $this->playerCommandService->update($player, $request->toDTO());

        $this->setFlashAlert('success', 'Player updated successfully!');

        [$returnRouteName, $returnRouteParams] = $this->getReturnRoute($request, $group, $member);

        return redirect()->route($returnRouteName, $returnRouteParams);
    }

    /**
     * Remove a player.
     */
    public function destroy(Request $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->authorizeMemberPlayerManagement($request, $group, $member, 'manage your own players.');
        $this->ensurePlayerBelongsToMember($player, $member);

        $this->playerCommandService->delete($player);

        $this->setFlashAlert('success', 'Player deleted successfully!');

        [$returnRouteName, $returnRouteParams] = $this->getReturnRoute($request, $group, $member);

        return redirect()->route($returnRouteName, $returnRouteParams);
    }

    /**
     * Resolve player route base name for regular and admin manage pages.
     */
    private function getRouteBaseName(Request $request): string
    {
        return $this->isManageRoute($request)
            ? 'groups.manage.members.players'
            : 'groups.members.players';
    }

    /**
     * Determine if the current request is through the admin manage-group player routes.
     */
    private function isManageRoute(Request $request): bool
    {
        return $request->routeIs('groups.manage.members.players.*');
    }

    /**
     * Resolve return route target for the current context.
     * Regular member flows return to group show; admin manage flows return to manage group with member selected.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function getReturnRoute(Request $request, Group $group, Member $member): array
    {
        if ($this->isManageRoute($request)) {
            return ['groups.edit', [
                'group' => $group,
                'member' => $member->ulid,
            ]];
        }

        return ['groups.show', [
            'group' => $group,
        ]];
    }

    /**
     * Resolve the player limit for the current request context.
     */
    private function getEffectivePlayerLimit(Request $request, Group $group): int
    {
        if ($this->isManageRoute($request) && $group->isAdminOrOwner($request->user())) {
            return $group->player_limit;
        }

        return Group::REGULAR_MEMBER_PLAYER_LIMIT;
    }

    /**
     * Authorize whether a user can manage players for the selected member in this route context.
     */
    private function authorizeMemberPlayerManagement(Request $request, Group $group, Member $member, string $message): void
    {
        if ($member->user_id === $request->user()->id) {
            return;
        }

        if ($this->isManageRoute($request) && $group->isAdminOrOwner($request->user())) {
            return;
        }

        abort(403, 'You can only '.$message);
    }

    /**
     * Ensure the selected player belongs to the selected member.
     */
    private function ensurePlayerBelongsToMember(Player $player, Member $member): void
    {
        if ($player->member_id !== $member->id) {
            abort(404, 'Player cannot be found for this member.');
        }
    }
}
