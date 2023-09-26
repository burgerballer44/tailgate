<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\MemberMustBeInGroup;
use App\Http\Middleware\PlayerMustBelongToGroup;
use App\Http\Requests\Group\StorePlayerRequest;
use App\Http\Requests\Group\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Group;
use App\Models\Member;
use App\Models\Player;

class PlayerController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(MemberMustBeInGroup::class);
        $this->middleware(PlayerMustBelongToGroup::class)->only('update', 'destroy');
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
}
