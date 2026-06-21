<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Exceptions\PredictionPolicyViolationException;
use App\Http\Requests\Group\StorePlayerRequest;
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
    /**
     * Build the developer player controller with player command operations.
     *
     * @param PlayerCommandInterface $playerCommandService Service responsible for player and prediction write operations.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private PlayerCommandInterface $playerCommandService
    ) {}

    /**
     * Display a paginated list of players for a group member.
     *
     * @param Request $request Incoming request context for pagination and future filters.
     * @param Group $group Route-bound group context.
     * @param Member $member Route-bound member whose players are being listed.
     * @return View Renders the developer player index for the selected member.
     */
    public function index(Request $request, Group $group, Member $member): View
    {
        return view('developer.players.index', [
            'group' => $group,
            'member' => $member,
            'players' => $member->players()->paginate(),
        ]);
    }

    /**
     * Show the form for adding a player to a member.
     *
     * @param Group $group Route-bound group context.
     * @param Member $member Route-bound member who will own the new player.
     * @return View Renders the developer player create form.
     */
    public function create(Group $group, Member $member): View
    {
        return view('developer.players.create', [
            'group' => $group,
            'member' => $member,
        ]);
    }

    /**
     * Create a player for the selected member.
     *
     * @param StorePlayerRequest $request Validated request containing new player details.
     * @param Group $group Route-bound group context for post-create navigation.
     * @param Member $member Route-bound member receiving the new player.
     * @return RedirectResponse Redirects to the member's player index after creation.
     */
    public function store(StorePlayerRequest $request, Group $group, Member $member): RedirectResponse
    {
        $this->playerCommandService->createForMember($member, $request->toDTO());

        $this->setFlashAlert('success', 'Player added successfully!');

        return redirect()->route('developer.groups.members.players.index', [$group, $member]);
    }

    /**
     * Show one player and its predictions.
     *
     * @param Group $group Route-bound group context.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player being viewed.
     * @return View Renders the player detail view with paginated predictions.
     */
    public function show(Group $group, Member $member, Player $player): View
    {
        return view('developer.players.show', [
            'group' => $group,
            'member' => $member,
            'player' => $player->load('member.user'),
            'predictions' => $player->predictions()->with(['game.homeTeam', 'game.awayTeam'])->paginate(),
        ]);
    }

    /**
     * Show the form for editing a player.
     *
     * @param Group $group Route-bound group context.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player being edited.
     * @return View Renders the player edit form.
     */
    public function edit(Group $group, Member $member, Player $player): View
    {
        return view('developer.players.edit', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
        ]);
    }

    /**
     * Update an existing player.
     *
     * @param UpdatePlayerRequest $request Validated request containing updated player values.
     * @param Group $group Route-bound group context for post-update navigation.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player being updated.
     * @return RedirectResponse Redirects to the member player index after update.
     */
    public function update(UpdatePlayerRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerCommandService->update($player, $request->toDTO());

        $this->setFlashAlert('success', 'Player updated successfully!');

        return redirect()->route('developer.groups.members.players.index', [$group, $member]);
    }

    /**
     * Remove a player from a member.
     *
     * @param Group $group Route-bound group context for post-delete navigation.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player to delete.
     * @return RedirectResponse Redirects to the member player index after deletion.
     */
    public function destroy(Group $group, Member $member, Player $player): RedirectResponse
    {
        $this->playerCommandService->delete($player);

        $this->setFlashAlert('success', 'Player removed successfully!');

        return redirect()->route('developer.groups.members.players.index', [$group, $member]);
    }

    /**
     * Show the prediction submission form for a player.
     *
     * @param Group $group Route-bound group used to scope eligible prediction games.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player submitting predictions.
     * @return View Renders the prediction submission form.
     */
    public function createPrediction(Group $group, Member $member, Player $player): View
    {
        // Predictions are limited to followed-season games so members cannot predict unrelated schedules.
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

    /**
     * Submit a new prediction for a player.
     *
     * @param SubmitPredictionRequest $request Validated request containing prediction data.
     * @param Group $group Route-bound group context for redirect routing.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player receiving the new prediction.
     * @return RedirectResponse Redirects back to the player detail or back with input when policy validation fails.
     */
    public function submitPrediction(SubmitPredictionRequest $request, Group $group, Member $member, Player $player): RedirectResponse
    {
        try {
            $this->playerCommandService->submitPrediction($player, $request->toDTO());

            $this->setFlashAlert('success', 'Prediction submitted successfully!');
        } catch (PredictionPolicyViolationException $exception) {
            $this->setFlashAlert('error', $exception->getMessage());

            return redirect()->back()->withInput();
        }

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }

    /**
     * Update an existing player prediction.
     *
     * @param UpdatePredictionRequest $request Validated request with updated prediction values.
     * @param Group $group Route-bound group context for redirect routing.
     * @param Member $member Route-bound member that owns the player.
     * @param Player $player Route-bound player that owns the prediction.
     * @param Prediction $prediction Route-bound prediction being updated.
     * @return RedirectResponse Redirects back to the player detail or back with input when policy validation fails.
     */
    public function updatePrediction(UpdatePredictionRequest $request, Group $group, Member $member, Player $player, Prediction $prediction): RedirectResponse
    {
        try {
            $this->playerCommandService->updatePrediction($prediction, $request->toDTO());

            $this->setFlashAlert('success', 'Prediction updated successfully!');
        } catch (PredictionPolicyViolationException $exception) {
            $this->setFlashAlert('error', $exception->getMessage());

            return redirect()->back()->withInput();
        }

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }

    /**
     * Show the form for editing an existing prediction.
     *
     * @param Group $group Route-bound group context.
     * @param Member $member Route-bound member context.
     * @param Player $player Route-bound player context.
     * @param Prediction $prediction Route-bound prediction being edited.
     * @return View Renders the prediction edit screen.
     */
    public function editPrediction(Group $group, Member $member, Player $player, Prediction $prediction): View
    {
        return view('developer.players.edit-prediction', [
            'group' => $group,
            'member' => $member,
            'player' => $player,
            'prediction' => $prediction,
        ]);
    }

    /**
     * Delete an existing prediction.
     *
     * @param Group $group Route-bound group context for redirect routing.
     * @param Member $member Route-bound member context.
     * @param Player $player Route-bound player context.
     * @param Prediction $prediction Route-bound prediction to delete.
     * @return RedirectResponse Redirects back to the player detail after deletion.
     */
    public function destroyPrediction(Group $group, Member $member, Player $player, Prediction $prediction): RedirectResponse
    {
        $this->playerCommandService->deletePrediction($prediction);

        $this->setFlashAlert('success', 'Prediction deleted successfully!');

        return redirect()->route('developer.groups.members.players.show', [$group, $member, $player]);
    }
}
