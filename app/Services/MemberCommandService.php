<?php

namespace App\Services;

use App\DTO\ValidatedMemberData;
use App\Models\Group;
use App\Models\GroupRole;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Services\Contracts\MemberCommandInterface;

/**
 * Executes membership lifecycle actions within group administration workflows.
 * Centralizes role and status persistence for consistent member management behavior.
 */
class MemberCommandService implements MemberCommandInterface
{
    /**
     * Adds a member to a group with normalized role and status defaults.
     *
     * @param  Group  $group  The group to add the member to.
     * @param  ValidatedMemberData  $data  Validated member data including user_id and role.
     * @return Member  The created member instance.
     */
    public function createForGroup(Group $group, ValidatedMemberData $data): Member
    {
        $memberData = [
            'user_id' => $data->user_id,
            'role' => $data->role?->value ?? GroupRole::GROUP_MEMBER->value,
            'status' => $data->status?->value ?? MemberStatus::APPROVED->value,
        ];

        return $group->members()->create($memberData);
    }

    /**
     * Applies role and status changes to an existing member.
     *
     * @param  Member  $member  The member to update.
     * @param  ValidatedMemberData  $data  Validated data containing member information to update.
     * @return Member  The updated member instance.
     */
    public function update(Member $member, ValidatedMemberData $data): Member
    {
        $updateData = [];

        if ($data->role !== null) {
            $updateData['role'] = $data->role->value;
        }

        if ($data->status !== null) {
            $updateData['status'] = $data->status->value;
        }

        $member->fill($updateData);
        $member->save();

        return $member;
    }

    /**
     * Removes a member record while enforcing minimum-admin safety constraints.
     *
     * @param  Member  $member  The member to delete.
     *
     * @throws \Exception If deleting would violate admin minimum requirements.
     */
    public function delete(Member $member): void
    {
        $group = $member->group;

        if (
            $group->admin->count() == Group::MIN_NUMBER_ADMINS &&
            $group->admin->first()->id == $member->id
        ) {
            throw new \Exception('Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.');
        }

        Member::destroy($member->getKey());
    }
}
