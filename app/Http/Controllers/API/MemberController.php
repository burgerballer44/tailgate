<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use App\Models\Member;
use App\Services\MemberService;
use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Http\Middleware\MemberMustBeInGroup;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Requests\Group\StoreMemberRequest;
use App\Http\Requests\Group\UpdateMemberRequest;
use Illuminate\Routing\Controllers\HasMiddleware;

class MemberController extends Controller implements HasMiddleware
{
    public function __construct(
        private MemberService $memberService
    ) {}

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
        $member = $this->memberService->createForGroup($group, $request->toDTO());

        return new MemberResource($member);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request, Group $group, Member $member)
    {
        $this->memberService->update($member, $request->toDTO());

        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Group $group, Member $member)
    {
        try {
            $this->memberService->delete($member);
            return response()->json([], 202);
        } catch (\Exception $e) {
            return response()->json(['data' => ['member_id' => [$e->getMessage()]]], 422);
        }
    }
}
