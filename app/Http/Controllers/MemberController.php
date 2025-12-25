<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Group\StoreMemberRequest;
use App\Http\Requests\Group\UpdateMemberRequest;

class MemberController extends Controller
{
    public function __construct(
        private MemberService $memberService
    ) {}

    public function index(Request $request, Group $group): View
    {
        return view('admin.members.index', [
            'group' => $group,
            'members' => $group->members()->with('user')->paginate(),
        ]);
    }

    public function create(Group $group): View
    {
        return view('admin.members.create', [
            'group' => $group,
            'users' => User::get()->makeVisible(['id']),
        ]);
    }

    public function store(StoreMemberRequest $request, Group $group): RedirectResponse
    {
        $this->memberService->createForGroup($group, $request->toDTO());

        $this->setFlashAlert('success', 'Member added successfully!');

        return redirect()->route('groups.members.index', $group);
    }

    public function show(Group $group, Member $member): View
    {
        return view('admin.members.show', [
            'group' => $group,
            'member' => $member->load(['user', 'players']),
        ]);
    }

    public function edit(Group $group, Member $member): View
    {
        return view('admin.members.edit', [
            'group' => $group,
            'member' => $member,
            'users' => User::get()->makeVisible(['id']),
        ]);
    }

    public function update(UpdateMemberRequest $request, Group $group, Member $member): RedirectResponse
    {
        $this->memberService->update($member, $request->toDTO());

        $this->setFlashAlert('success', 'Member updated successfully!');

        return redirect()->route('groups.members.index', $group);
    }

    public function destroy(Group $group, Member $member): RedirectResponse
    {
        $this->memberService->delete($member);

        $this->setFlashAlert('success', 'Member removed successfully!');

        return redirect()->route('groups.members.index', $group);
    }
}