<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\StoreDeveloperPlayerRequest;
use App\Http\Requests\Group\SubmitPredictionRequest;
use App\Http\Requests\Group\UpdatePlayerRequest;
use App\Http\Requests\Group\UpdatePredictionRequest;
use App\Models\Game;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
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

    public function store(StoreDeveloperPlayerRequest $request, Group $group, Member $member): RedirectResponse
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
            'predictions' => $player->predictions()->with(['game.homeTeam', 'game.awayTeam'])->paginate(),
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

    public function createPrediction(Group $group, Member $member, Player $player): View
    {
        // Get games that are available for predictions (based on group follows)
        $games = Game::whereHas('season.follows', function ($query) use ($group) {
            $query->where('group_id', $group->id);
        })->with(['homeTeam', 'awayTeam'])->get();

        return view('developer.players.submit-prediction', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'games' => $games->makeVisible(['id']),
        ]);
    }

    public function submitPrediction(SubmitPredictionRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerCommandService->submitPrediction($player, $request->toDTO());

        $this->setFlashAlert('success', 'Prediction submitted successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }

    public function updatePrediction(UpdatePredictionRequest $request, Group $group, Member $member, Player $player, Prediction $prediction): RedirectResponse
    {
        $this->playerCommandService->updatePrediction($prediction, $request->toDTO());

        $this->setFlashAlert('success', 'Prediction updated successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }

    public function editPrediction(Group $group, Member $member, Player $player, Prediction $prediction): View
    {
        return view('developer.players.edit-prediction', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'prediction' => $prediction,
        ]);
    }

    public function destroyPrediction(Group $group, Member $member, Player $player, Prediction $prediction): RedirectResponse
    {
        $this->playerCommandService->deletePrediction($prediction);

        $this->setFlashAlert('success', 'Prediction deleted successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }
}
