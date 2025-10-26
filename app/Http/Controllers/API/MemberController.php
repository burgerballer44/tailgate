<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use App\Models\Member;
use App\Models\GroupRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Http\Middleware\MemberMustBeInGroup;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Group\StoreMemberRequest;
use App\Http\Requests\Group\UpdateMemberRequest;
use Illuminate\Routing\Controllers\HasMiddleware;

class MemberController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(MemberMustBeInGroup::class, only: ['update', 'destroy']),
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMemberRequest $request, Group $group)
    {
        $validated = $request->validated();

        $member = $group->members()->save(new Member([
            'user_id' => $validated['user_id'],
            'role' => GroupRole::GROUP_MEMBER->value,
        ]));

        return new MemberResource($member);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request, Group $group, Member $member)
    {
        $validated = $request->validated();

        $member->fill($validated);

        $member->save();

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group, Member $member)
    {
        if (
            $group->admin->count() == Group::MIN_NUMBER_ADMINS &&
            $group->admin->first() == $member
        ) {
            return response()->json(['data' => ['member_id' => ['Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.']]], 422);
        }

        $member->delete();

        return response()->json([], 202);
    }
}
