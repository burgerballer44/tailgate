<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Models\Group;
use App\Models\Follow;
use App\Models\Season;
use Illuminate\Http\Request;
use App\Services\GroupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;

class GroupController extends Controller
{
    public function __construct(
        private GroupService $groupService
    ) {}

    public function index(Request $request): View
    {
        return view('admin.groups.index', [
            'groups' => $this->groupService->query($request->query())->paginate(),
            'users' => User::get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.groups.create', [
            'users' => User::get(),
        ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->groupService->create($request->toDTO());

        $this->setFlashAlert('success', 'Group created successfully!');

        return redirect()->route('groups.index');
    }

    public function show(Group $group): View
    {
        $group->load([
            'owner',
            'members.user',
            'players.member.user',
            'players.scores.player.member.user',
            'players.scores.game.homeTeam',
            'players.scores.game.awayTeam',
            'follow.team',
            'follow.season'
        ]);

        $scores = $group->players->flatMap->scores->sortByDesc('created_at');
        $perPage = 20;
        $currentPage = request()->get('page', 1);
        $items = $scores->forPage($currentPage, $perPage);
        $paginatedScores = new \Illuminate\Pagination\LengthAwarePaginator($items, $scores->count(), $perPage, $currentPage, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);

        return view('admin.groups.show', [
            'group' => $group,
            'scores' => $paginatedScores,
        ]);
    }

    public function edit(Group $group): View
    {
        return view('admin.groups.edit', [
            'group' => $group,
            'users' => User::get(),
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->groupService->update($group, $request->toDTO());

        $this->setFlashAlert('success', 'Group updated successfully!');

        return redirect()->route('groups.index');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $this->groupService->delete($group);

        $this->setFlashAlert('success', 'Group deleted successfully!');

        return redirect()->route('groups.index');
    }

    public function createFollowTeam(Group $group): View
    {
        $teams = Team::all();
        $seasons = Season::all();

        return view('admin.groups.follow-team', compact('group', 'teams', 'seasons'));
    }

    public function followTeam(FollowTeamRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupService->followTeam($group, $request->toDTO());

            $this->setFlashAlert('success', 'Team followed successfully!');

            return redirect()->route('groups.show', $group);
        } catch (\Exception $e) {
            $this->setFlashAlert('error', $e->getMessage());

            return redirect()->back();
        }
    }

    public function removeFollow(Group $group, Follow $follow): RedirectResponse
    {
        $this->groupService->removeFollow($group);

        $this->setFlashAlert('success', 'Follow removed successfully!');

        return redirect()->route('groups.show', $group);
    }
}
