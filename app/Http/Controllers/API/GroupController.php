<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\FollowBelongsToGroup;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Resources\FollowResource;
use App\Http\Resources\GroupResource;
use App\Models\Follow;
use App\Models\Group;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GroupController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(FollowBelongsToGroup::class, only: ['removeFollow']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GroupResource::collection(Group::filter(request()->input())->with('owner')->paginate(500));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGroupRequest $request)
    {
        $validated = $request->validated();

        $group = new Group($validated);

        $group->save();

        return new GroupResource($group);
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group)
    {
        return new GroupResource($group);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group)
    {
        $validated = $request->validated();

        $group->fill($validated);

        $group->save();

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json([], 202);
    }

    /**
     * Follow a team
     */
    public function followTeam(FollowTeamRequest $request, Group $group)
    {
        $validated = $request->validated();

        if ($group->follow) {
            return response()->json(['data' => ['follow' => ['This group is already following a team.']]], 422);
        }

        $follow = $group->follow()->save(
            new Follow([
                'team_id' => $validated['team_id'],
                'season_id' => $validated['season_id'],
            ])
        );

        return new FollowResource($follow);
    }

    /**
     * Remove a follow
     */
    public function removeFollow(Group $group, Follow $follow)
    {
        $follow->delete();

        return response()->json([], 202);
    }
}
