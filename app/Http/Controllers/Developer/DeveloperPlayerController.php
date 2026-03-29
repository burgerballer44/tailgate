<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\StorePlayerRequest;
use App\Http\Requests\Group\SubmitScoreRequest;
use App\Http\Requests\Group\UpdatePlayerRequest;
use App\Http\Requests\Group\UpdateScoreRequest;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Score;
use App\Services\Contracts\PlayerCommandInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperPlayerController extends Controller
{
    public function __construct(
        private PlayerCommandInterface $playerCommandService
    ) {}

    public function index(Request $request, Group $group, Member $member): View
    {
        return view('developer.players.index', [
            'group' => $group,
            'member' => $member,
            'players' => $member->players()->paginate(),
        ]);
    }

    public function create(Group $group, Member $member): View
    {
        return view('developer.players.create', [
            'group' => $group,
            'member' => $member,
        ]);
    }

    public function store(StorePlayerRequest $request, Group $group, Member $member): RedirectResponse
    {
        $this->playerCommandService->createForMember($member, $request->toDTO());

        $this->setFlashAlert('success', 'Player added successfully!');

        return redirect()->route('developer.groups.members.players.index', [$group, $member]);
    }

    public function show(Group $group, Member $member, Player $player): View
    {
        return view('developer.players.show', [
            'group' => $group,
            'member' => $member,
            'player' => $player->load('member.user'),
            'scores' => $player->scores()->with(['game.homeTeam', 'game.awayTeam'])->paginate(),
        ]);
    }

    public function edit(Group $group, Member $member, Player $player): View
    {
        return view('developer.players.edit', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
        ]);
    }

    public function update(UpdatePlayerRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerCommandService->update($player, $request->toDTO());

        $this->setFlashAlert('success', 'Player updated successfully!');

        return redirect()->route('developer.groups.members.players.index', [$group, $member]);
    }

    public function destroy(Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerCommandService->delete($player);

        $this->setFlashAlert('success', 'Player removed successfully!');

        return redirect()->route('developer.groups.members.players.index', [$group, $member]);
    }

    public function createScore(Group $group, Member $member, Player $player): View
    {
        // Get games that are available for scoring (based on group follows)
        $games = Game::whereHas('season.follows', function ($query) use ($group) {
            $query->where('group_id', $group->id);
        })->with(['homeTeam', 'awayTeam'])->get();

        return view('developer.players.submit-score', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'games' => $games->makeVisible(['id']),
        ]);
    }

    public function submitScore(SubmitScoreRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerCommandService->submitScore($player, $request->toDTO());

        $this->setFlashAlert('success', 'Score submitted successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }

    public function updateScore(UpdateScoreRequest $request, Group $group, Member $member, Player $player, Score $score): RedirectResponse
    {
        $this->playerCommandService->updateScore($score, $request->toDTO());

        $this->setFlashAlert('success', 'Score updated successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }

    public function editScore(Group $group, Member $member, Player $player, Score $score): View
    {
        return view('developer.players.edit-score', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'score' => $score,
        ]);
    }

    public function destroyScore(Group $group, Member $member, Player $player, Score $score): RedirectResponse
    {
        $this->playerCommandService->deleteScore($score);

        $this->setFlashAlert('success', 'Score deleted successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }
}
