<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\StoreMemberRequest;
use App\Http\Requests\Group\UpdateMemberRequest;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Services\Contracts\MemberCommandInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeveloperMemberController extends Controller
{
    /**
     * Build the developer member controller with member write operations.
     *
     * @param MemberCommandInterface $memberCommandService Service that manages member create, update, and delete actions.
     * @return void Initializes controller dependencies.
     */
    public function __construct(
        private MemberCommandInterface $memberCommandService
    ) {}

    /**
     * Display a paginated list of members for the given group.
     *
     * @param Request $request Incoming request context for pagination and future filters.
     * @param Group $group Route-bound group whose members are being managed.
     * @return View Renders the developer member index for the selected group.
     */
    public function index(Request $request, Group $group): View
    {
        return view('developer.members.index', [
            'group' => $group,
            'members' => $group->members()->with('user')->paginate(),
        ]);
    }

    /**
     * Show the form for adding a new member to a group.
     *
     * @param Group $group Route-bound group receiving the new member.
     * @return View Renders the member create form with selectable users.
     */
    public function create(Group $group): View
    {
        return view('developer.members.create', [
            'group' => $group,
            'users' => User::query()->get()->makeVisible(['id']),
            'roleOptions' => collect(GroupRole::cases())
                ->mapWithKeys(fn (GroupRole $role): array => [$role->value => $role->value])
                ->toArray(),
            'statusOptions' => collect(MemberStatus::cases())
                ->mapWithKeys(fn (MemberStatus $status): array => [$status->value => $status->value])
                ->toArray(),
            'defaultStatus' => MemberStatus::APPROVED->value,
        ]);
    }

    /**
     * Create a new member for the selected group.
     *
     * @param StoreMemberRequest $request Validated member payload for the target group.
     * @param Group $group Route-bound group where the member will be created.
     * @return RedirectResponse Redirects back to the group member list after creation.
     */
    public function store(StoreMemberRequest $request, Group $group): RedirectResponse
    {
        $this->memberCommandService->createForGroup($group, $request->toDTO());

        $this->setFlashAlert('success', 'Member added successfully!');

        return redirect()->route('developer.groups.members.index', $group);
    }

    /**
     * Show a single group member and their players.
     *
     * @param Group $group Route-bound group that owns the member.
     * @param Member $member Route-bound member being viewed.
     * @return View Renders the developer member detail page.
     */
    public function show(Group $group, Member $member): View
    {
        return view('developer.members.show', [
            'group' => $group,
            'member' => $member->load('user'),
            'players' => $member->players()->paginate(),
        ]);
    }

    /**
     * Show the form for editing a group member.
     *
     * @param Group $group Route-bound group that owns the member.
     * @param Member $member Route-bound member being edited.
     * @return View Renders the member edit form with selectable users.
     */
    public function edit(Group $group, Member $member): View
    {
        return view('developer.members.edit', [
            'group' => $group,
            'member' => $member,
            'users' => User::query()->get()->makeVisible(['id']),
            'roleOptions' => collect(GroupRole::cases())
                ->mapWithKeys(fn (GroupRole $role): array => [$role->value => $role->value])
                ->toArray(),
            'statusOptions' => collect(MemberStatus::cases())
                ->mapWithKeys(fn (MemberStatus $status): array => [$status->value => $status->value])
                ->toArray(),
            'defaultStatus' => MemberStatus::APPROVED->value,
        ]);
    }

    /**
     * Update an existing group member.
     *
     * @param UpdateMemberRequest $request Validated member update payload.
     * @param Group $group Route-bound group that owns the member.
     * @param Member $member Route-bound member being updated.
     * @return RedirectResponse Redirects back to the group member list after update.
     */
    public function update(UpdateMemberRequest $request, Group $group, Member $member): RedirectResponse
    {
        $this->memberCommandService->update($member, $request->toDTO());

        $this->setFlashAlert('success', 'Member updated successfully!');

        return redirect()->route('developer.groups.members.index', $group);
    }

    /**
     * Remove a member from the group.
     *
     * @param Group $group Route-bound group that owns the member.
     * @param Member $member Route-bound member to remove.
     * @return RedirectResponse Redirects back to the group member list after deletion.
     */
    public function destroy(Group $group, Member $member): RedirectResponse
    {
        $this->memberCommandService->delete($member);

        $this->setFlashAlert('success', 'Member removed successfully!');

        return redirect()->route('developer.groups.members.index', $group);
    }
}
