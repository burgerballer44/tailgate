<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Score;
use App\Services\PlayerService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Group\StorePlayerRequest;
use App\Http\Requests\Group\UpdatePlayerRequest;
use App\Http\Requests\Group\SubmitScoreRequest;
use App\Http\Requests\Group\UpdateScoreRequest;

class AdminPlayerController extends Controller
{
    public function __construct(
        private PlayerService $playerService
    ) {}

    public function index(Request $request, Group $group, Member $member): View
    {
        return view('admin.players.index', [
            'group' => $group,
            'member' => $member,
            'players' => $member->players()->paginate(),
        ]);
    }

    public function create(Group $group, Member $member): View
    {
        return view('admin.players.create', [
            'group' => $group,
            'member' => $member,
        ]);
    }

    public function store(StorePlayerRequest $request, Group $group, Member $member): RedirectResponse
    {
        $this->playerService->createForMember($member, $request->toDTO());

        $this->setFlashAlert('success', 'Player added successfully!');

        return redirect()->route('admin.groups.members.players.index', [$group, $member]);
    }

    public function show(Group $group, Member $member, Player $player): View
    {
        return view('admin.players.show', [
            'group' => $group,
            'member' => $member,
            'player' => $player->load('member.user'),
            'scores' => $player->scores()->with(['game.homeTeam', 'game.awayTeam'])->paginate(),
        ]);
    }

    public function edit(Group $group, Member $member, Player $player): View
    {
        return view('admin.players.edit', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
        ]);
    }

    public function update(UpdatePlayerRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerService->update($player, $request->toDTO());

        $this->setFlashAlert('success', 'Player updated successfully!');

        return redirect()->route('admin.groups.members.players.index', [$group, $member]);
    }

    public function destroy(Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerService->delete($player);

        $this->setFlashAlert('success', 'Player removed successfully!');

        return redirect()->route('admin.groups.members.players.index', [$group, $member]);
    }

    public function createScore(Group $group, Member $member, Player $player): View
    {
        // Get games that are available for scoring (based on group follows)
        $games = Game::whereHas('season.follows', function ($query) use ($group) {
            $query->where('group_id', $group->id);
        })->with(['homeTeam', 'awayTeam'])->get();

        return view('admin.players.submit-score', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'games' => $games->makeVisible(['id']),
        ]);
    }

    public function submitScore(SubmitScoreRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerService->submitScore($player, $request->toDTO());

        $this->setFlashAlert('success', 'Score submitted successfully!');

        return redirect()->route('admin.groups.members.players.show', [$group, $member, $player]);
    }

    public function updateScore(UpdateScoreRequest $request, Group $group, Member $member, Player $player, Score $score): RedirectResponse
    {
        $this->playerService->updateScore($score, $request->toDTO());

        $this->setFlashAlert('success', 'Score updated successfully!');

        return redirect()->route('admin.groups.members.players.show', [$group, $member, $player]);
    }

    public function editScore(Group $group, Member $member, Player $player, Score $score): View
    {
        return view('admin.players.edit-score', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'score' => $score,
        ]);
    }

    public function destroyScore(Group $group, Member $member, Player $player, Score $score): RedirectResponse
    {
        $this->playerService->deleteScore($score);

        $this->setFlashAlert('success', 'Score deleted successfully!');

        return redirect()->route('admin.groups.members.players.show', [$group, $member, $player]);
    }
}