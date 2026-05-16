<?php

namespace App\Http\Controllers;

use App\DTO\ValidatedMemberData;
use App\Http\Requests\Group\FollowTeamRequest;
use App\Http\Requests\Group\JoinGroupRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UserUpdateGroupRequest;
use App\Models\Follow;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Services\Contracts\GroupCommandInterface;
use App\Services\Contracts\GroupQueryInterface;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\SeasonQueryInterface;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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
        private GroupCommandInterface $groupCommandService,
        private GroupQueryInterface $groupQueryService,
        private MemberCommandInterface $memberCommandService,
        private TeamQueryInterface $teamQueryService,
        private SeasonQueryInterface $seasonQueryService,
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
     * @param  StoreGroupRequest  $request  The validated request containing group data
     * @return RedirectResponse Redirects to dashboard with success message
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        // create the group
        $group = $this->groupCommandService->create($request->toDTO());

        // set a success flash message that includes the invite code for the user to share
        $this->setFlashAlert('success', 'Group created successfully! Invite code: '.$group->invite_code);

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
     * @param  JoinGroupRequest  $request  The validated request containing the invite code
     * @return RedirectResponse Redirects back with success/error messages
     */
    public function requestJoin(JoinGroupRequest $request): RedirectResponse
    {
        // find the group by invite code
        $group = $this->groupQueryService->findByInviteCode($request->invite_code);

        // if no group found with that code, show error and redirect back
        if (! $group) {
            $this->setFlashAlert('error', 'Invalid invite code.');

            return redirect()->back();
        }

        // check if the current user is already a member of this group
        // this prevents duplicate memberships
        if ($this->groupQueryService->isUserAlreadyMember($group, $request->user()->id)) {
            $this->setFlashAlert('error', 'You are already a member of this group.');

            return redirect()->back();
        }

        // check if the group has reached its member limit
        if ($this->groupQueryService->isGroupMemberLimitReached($group)) {
            $this->setFlashAlert('error', 'Group member limit reached.');

            return redirect()->back();
        }

        // add the user as a pending member of the group
        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $request->user()->id,
            'role' => GroupRole::GROUP_MEMBER,
            'status' => MemberStatus::PENDING,
        ]);

        $this->groupCommandService->addMember($group, $memberData);

        // show success message and redirect to dashboard
        $this->setFlashAlert('success', 'Successfully joined the group!');

        return redirect()->route('dashboard');
    }

    /**
     * Display the specified group details.
     *
     * This method shows detailed information about a group, including members,
     * pending join requests, and other group data. Any member can view details.
     *
     * @param  Group  $group  The group to display
     * @return View Returns the group details view
     */
    public function show(Group $group): View
    {
        $group->load(['follow.team', 'follow.season']);

        return view('groups.show', ['group' => $group]);
    }

    /**
     * Show the form for editing the specified group.
     *
     * This method displays the group management form where owners and admins
     * can manage group settings, approve/reject join requests, and manage members.
     *
     * @param  Group  $group  The group to edit
     * @return View Returns the group edit view
     */
    public function edit(Group $group): View
    {
        $group->load(['follow.team', 'follow.season']);

        return view('groups.edit', ['group' => $group]);
    }

    /**
     * Update the specified group.
     *
     * This method processes updates to group settings. Only owners and admins can update.
     *
     * @param  UserUpdateGroupRequest  $request  The validated update request
     * @param  Group  $group  The group to update
     * @return RedirectResponse Redirects back with success message
     */
    public function update(UserUpdateGroupRequest $request, Group $group): RedirectResponse
    {
        // the owner_id is required in the DTO for validation purposes
        $this->groupCommandService->update($group, $request->toDTO($group->owner_id));

        $this->setFlashAlert('success', 'Group updated successfully!');

        return redirect()->route('groups.show', $group);
    }

    /**
     * Approve a pending member join request.
     *
     * This method approves a pending join request, changing the member's status to approved.
     * Only group owners and admins can approve requests.
     *
     * @param  Group  $group  The group
     * @param  Member  $member  The pending member to approve
     * @return RedirectResponse Redirects back with success message
     */
    public function approveMember(Group $group, Member $member): RedirectResponse
    {
        // ensure member is pending
        if ($member->status !== MemberStatus::PENDING->value) {
            abort(404, 'Invalid member or not pending.');
        }

        $memberData = ValidatedMemberData::fromArray([
            'user_id' => $member->user_id,
            'role' => GroupRole::from($member->role),
            'status' => MemberStatus::APPROVED,
        ]);

        $this->memberCommandService->update($member, $memberData);

        $this->setFlashAlert('success', 'Member approved successfully!');

        return redirect()->back();
    }

    /**
     * Reject a pending member join request.
     *
     * This method rejects a pending join request, either by deleting the member
     * or changing status to rejected. Only group owners and admins can reject requests.
     *
     * @param  Group  $group  The group
     * @param  Member  $member  The pending member to reject
     * @return RedirectResponse Redirects back with success message
     */
    public function rejectMember(Group $group, Member $member): RedirectResponse
    {
        // ensure member is pending
        if ($member->status !== MemberStatus::PENDING->value) {
            abort(404, 'Invalid member or not pending.');
        }

        $this->memberCommandService->delete($member);

        $this->setFlashAlert('success', 'Join request rejected.');

        return redirect()->back();
    }

    /**
     * Remove an approved member from the group.
     *
     * This method removes an approved member from the group. Only group owners and admins can remove members.
     * Cannot remove the owner or the last admin.
     *
     * @param  Group  $group  The group
     * @param  Member  $member  The member to remove
     * @return RedirectResponse Redirects back with success message
     */
    public function removeMember(Group $group, Member $member): RedirectResponse
    {
        // cannot remove the owner
        if ($member->user_id === $group->owner_id) {
            abort(403, 'Cannot remove the group owner.');
        }

        $this->memberCommandService->delete($member);

        $this->setFlashAlert('success', 'Member removed from group.');

        return redirect()->back();
    }

    /**
     * Show the form for following a team.
     *
     * This method displays the form where group admins can choose a team and season to follow.
     *
     * @param  Group  $group  The group to follow a team for
     * @return View Returns the follow team view
     */
    public function createFollowTeam(Group $group): View
    {
        $teams = $this->teamQueryService->getAvailableTeamsForFollow();
        $seasons = $this->seasonQueryService->getAvailableSeasonsForFollow();

        return view('groups.follow-team', compact('group', 'teams', 'seasons'));
    }

    /**
     * Follow a team for the group.
     *
     * This method processes the follow team request and creates the follow relationship.
     *
     * @param  FollowTeamRequest  $request  The validated request containing team and season data
     * @param  Group  $group  The group to follow the team
     * @return RedirectResponse Redirects back with success/error messages
     */
    public function followTeam(FollowTeamRequest $request, Group $group): RedirectResponse
    {
        try {
            $this->groupCommandService->followTeam($group, $request->toDTO());

            $this->setFlashAlert('success', 'Team followed successfully!');

            return redirect()->route('groups.show', $group);
        } catch (\Exception $e) {
            $this->setFlashAlert('error', $e->getMessage());

            return redirect()->back();
        }
    }

    /**
     * Remove a follow relationship.
     *
     * This method removes the follow relationship for the group.
     *
     * @param  Group  $group  The group to unfollow
     * @param  Follow  $follow  The follow to remove
     * @return RedirectResponse Redirects back with success message
     */
    public function removeFollow(Group $group, Follow $follow): RedirectResponse
    {
        $this->groupCommandService->removeFollow($group);

        $this->setFlashAlert('success', 'Follow removed successfully!');

        return redirect()->route('groups.show', $group);
    }
}
