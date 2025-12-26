<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupRole;
use App\Services\GroupService;
use App\DTO\ValidatedMemberData;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Group\StoreGroupRequest;

/**
 * GroupController handles user-facing group operations.
 *
 * This controller manages the creation of new groups and the process of joining
 * existing groups via invite codes. It serves regular users and focuses
 * on the social aspects of group formation and membership.
 */
class GroupController extends Controller
{
    /**
     * Create a new GroupController instance.
     *
     * We inject the GroupService to handle all group-related business logic,
     * keeping the controller focused on HTTP request/response handling.
     */
    public function __construct(
        private GroupService $groupService
    ) {}

    /**
     * Show the group creation form.
     *
     * This method displays the form where users can enter details to create
     * a new group. The form will collect the group name and handle setting
     * the owner automatically.
     *
     * @return View Returns the group creation view
     */
    public function create(): View
    {
        return view('groups.create');
    }

    /**
     * Store a newly created group.
     *
     * This method processes the group creation request. It uses the GroupService
     * to create the group with validated data, then redirects the user back to
     * their dashboard with a success message that includes the invite code for sharing.
     *
     * @param StoreGroupRequest $request The validated request containing group data
     * @return RedirectResponse Redirects to dashboard with success message
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        // create the group
        $group = $this->groupService->create($request->toDTO());

        // set a success flash message that includes the invite code for the user to share
        $this->setFlashAlert('success', 'Group created successfully! Invite code: ' . $group->invite_code);

        // redirect back to dashboard so user can see their new group
        return redirect()->route('dashboard');
    }

    /**
     * Show the group join by invite code form.
     *
     * This method displays the form where users can enter an invite code
     * to request joining an existing group.
     *
     * @return View Returns the group joining view
     */
    public function join(): View
    {
        return view('groups.join');
    }

    /**
     * Process a request to join a group via invite code.
     *
     * This method handles the user's request to join a group. It validates the
     * invite code, checks for existing membership, and adds the user as a member
     * if everything is valid. Currently uses direct joining, but can be extended
     * with owner confirmation logic later.
     *
     * @param Request $request The HTTP request containing the invite code
     * @return RedirectResponse Redirects back with success/error messages
     */
    public function requestJoin(Request $request): RedirectResponse
    {
        // validate that the invite code is provided and is a string
        $request->validate(['invite_code' => 'required|string']);

        // find the group by invite code
        $group = $this->groupService->findByInviteCode($request->invite_code);

        // if no group found with that code, show error and redirect back
        if (!$group) {
            $this->setFlashAlert('error', 'Invalid invite code.');
            return redirect()->back();
        }

        // check if the current user is already a member of this group
        // this prevents duplicate memberships
        if (GroupService::isUserAlreadyMember($group, $request->user()->id)) {
            $this->setFlashAlert('error', 'You are already a member of this group.');
            return redirect()->back();
        }

        // add the user as a member of the group with the default member role
        // Note: This is direct joining for now; owner confirmation can be added later
        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $request->user()->id,
            'role' => GroupRole::GROUP_MEMBER,
        ]);

        $this->groupService->addMember($group, $memberData);

        // show success message and redirect to dashboard
        $this->setFlashAlert('success', 'Successfully joined the group!');

        return redirect()->route('dashboard');
    }
}