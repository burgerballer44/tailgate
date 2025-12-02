<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use App\Models\Follow;
use App\Services\GroupService;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Http\Resources\FollowResource;
use App\Http\Middleware\FollowBelongsToGroup;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Group\UpdateGroupRequest;
use Illuminate\Routing\Controllers\HasMiddleware;

class GroupController extends Controller implements HasMiddleware
{
    public function __construct(
        private GroupService $groupService
    ) {}

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
        $group = $this->groupService->create($request->toDTO());

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
        $this->groupService->update($group, $request->toDTO());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group)
    {
        $this->groupService->delete($group);

        return response()->json([], 202);
    }

    /**
     * Follow a team
     */
    public function followTeam(FollowTeamRequest $request, Group $group)
    {
        try {
            $follow = $this->groupService->followTeam($group, $request->toDTO());

            return new FollowResource($follow);
        } catch (\Exception $e) {
            return response()->json(['data' => ['follow' => [$e->getMessage()]]], 422);
        }
    }

    /**
     * Remove a follow
     */
    public function removeFollow(Group $group, Follow $follow)
    {
        $this->groupService->removeFollow($group);

        return response()->json([], 202);
    }
}
