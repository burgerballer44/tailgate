<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\MemberMustBeInGroup;
use App\Http\Middleware\PlayerMustBelongToMember;
use App\Http\Middleware\ScoreMustBelongToPlayer;
use App\Http\Requests\Group\StorePlayerRequest;
use App\Http\Requests\Group\SubmitScoreRequest;
use App\Http\Requests\Group\UpdatePlayerRequest;
use App\Http\Requests\Group\UpdateScoreRequest;
use App\Http\Resources\PlayerResource;
use App\Http\Resources\ScoreResource;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;
use App\Models\Score;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PlayerController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(MemberMustBeInGroup::class),
            new Middleware(PlayerMustBelongToMember::class, only: [
                'update', 'destroy', 'submitScore', 'updateScore', 'destroyScore',
            ]),
            new Middleware(ScoreMustBelongToPlayer::class, only: ['updateScore', 'destroyScore']),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlayerRequest $request, Group $group, Member $member)
    {
        $validated = $request->validated();

        $player = $member->players()->save(new Player($validated));

        return new PlayerResource($player);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlayerRequest $request, Group $group, Member $member, Player $player)
    {
        $validated = $request->validated();

        $player->fill($validated);

        $player->save();

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group, Member $member, Player $player)
    {
        $player->delete();

        return response()->json([], 202);
    }

    /**
     * Submit a score
     */
    public function submitScore(
        SubmitScoreRequest $request,
        Group $group,
        Member $member,
        Player $player
    ) {
        $validated = $request->validated();

        $score = $player->scores()->save(new Score($validated));

        return new ScoreResource($score);
    }

    /**
     * Update a score
     */
    public function updateScore(
        UpdateScoreRequest $request,
        Group $group,
        Member $member,
        Player $player,
        Score $score,
    ) {
        $validated = $request->validated();

        $score->fill($validated);

        $score->save();

        return response()->noContent();
    }

    /**
     * Delete a score
     */
    public function destroyScore(
        Group $group,
        Member $member,
        Player $player,
        Score $score,
    ) {
        $score->delete();

        return response()->json([], 202);
    }
}
