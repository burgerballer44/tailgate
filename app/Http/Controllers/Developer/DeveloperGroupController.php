<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Score;
use App\Models\Team;
use App\Models\User;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperGroupController extends Controller
{
    public function __construct(
        private GroupCommandInterface $groupCommandService,
        private GroupQueryInterface $groupQueryService
    ) {}

    public function index(Request $request): View
    {
        return view('developer.groups.index', [
            'groups' => $this->groupQueryService->query($request->query())->paginate(),
            'users' => User::get(),
        ]);
    }

    public function create(): View
    {
        return view('developer.groups.create', [
            'users' => User::get(),
        ]);
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->groupCommandService->create($request->toDTO());

        $this->setFlashAlert('success', 'Group created successfully!');

        return redirect()->route('developer.groups.index');
    }

    /**
     * The show method handles displaying the details of a specific group,
     * including its members, players, and scores.
     * It also manages the active tab state based on the query parameter.
     * Depending on the active tab, it loads the necessary
     * relationships and data to be displayed in the view.
     * 
     * @param Request $request
     * @param Group $group
     * @return View
     */
    public function show(Request $request, Group $group): View
    {
        // these are the valid tabs that can be displayed in the group details view
        $validTabs = ['details', 'members', 'players', 'scores'];

        // determine the active tab based on the query parameter, defaulting to 'details' if not provided or invalid
        $activeTab = in_array($request->query('tab'), $validTabs, true)
            ? $request->query('tab')
            : 'details';

        if ($activeTab === 'details') {
            $group->load(['owner', 'follow.team'])
                ->loadCount(['members', 'players']);
        }

        if ($activeTab === 'members') {
            $group->load('members.user');
        }

        if ($activeTab === 'players') {
            $group->load('players.member.user');
        }

        $scores = null;
        if ($activeTab === 'scores') {
            $scores = Score::query()
                ->whereHas('player.member', fn ($query) => $query->where('group_id', $group->id))
                ->with([
                    'player.member.user',
                    'game.homeTeam',
                    'game.awayTeam',
                ])
                ->latest()
                ->paginate(perPage: 20, pageName: 'scores_page')
                ->appends(['tab' => 'scores']);
        }

        return view('developer.groups.show', [
            'group' => $group,
            'scores' => $scores,
            'activeTab' => $activeTab,
        ]);
    }

    public function edit(Group $group): View
    {
        return view('developer.groups.edit', [
            'group' => $group,
            'users' => User::get(),
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->groupCommandService->update($group, $request->toDTO());

        $this->setFlashAlert('success', 'Group updated successfully!');

        return redirect()->route('developer.groups.index');
    }

    public function destroy(Group $group): RedirectResponse
    {
        $this->groupCommandService->delete($group);

        $this->setFlashAlert('success', 'Group deleted successfully!');

        return redirect()->route('developer.groups.index');
    }

    public function createFollowTeam(Group $group): View
    {
        $teams = Team::all();

        return view('developer.groups.follow-team', compact('group', 'teams'));
    }

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

    public function removeFollow(Group $group, Follow $follow): RedirectResponse
    {
        $this->groupCommandService->removeFollow($group);

        $this->setFlashAlert('success', 'Follow removed successfully!');

        return redirect()->route('developer.groups.show', $group);
    }
}
