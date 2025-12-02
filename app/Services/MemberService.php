<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Group;
use App\Models\GroupRole;
use App\DTO\ValidatedMemberData;
use Illuminate\Contracts\Database\Eloquent\Builder;

class MemberService
{
    /**
     * Create a new member for a specific group.
     * This method handles member creation logic within a group context.
     *
     * @param Group $group The group to add the member to.
     * @param ValidatedMemberData $data Validated member data including user_id and role.
     * @return Member The created member instance.
     */
    public function createForGroup(Group $group, ValidatedMemberData $data): Member
    {
        $memberData = [
            'user_id' => $data->user_id,
            'role' => $data->role?->value ?? GroupRole::GROUP_MEMBER->value,
        ];

        return $group->members()->create($memberData);
    }

    /**
     * Update a member's information.
     * This method modifies member details.
     *
     * @param Member $member The member to update.
     * @param ValidatedMemberData $data Validated data containing member information to update.
     * @return Member The updated member instance.
     */
    public function update(Member $member, ValidatedMemberData $data): Member
    {
        $updateData = [];

        if ($data->role !== null) {
            $updateData['role'] = $data->role->value;
        }

        $member->fill($updateData);
        $member->save();

        return $member;
    }

    /**
     * Delete a member from the system.
     * This method permanently removes the member.
     *
     * @param Member $member The member to delete.
     * @throws \Exception If deleting would violate admin minimum requirements.
     */
    public function delete(Member $member): void
    {
        $group = $member->group;

        if (
            $group->admin->count() == \App\Models\Group::MIN_NUMBER_ADMINS &&
            $group->admin->first()->id == $member->id
        ) {
            throw new \Exception('Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.');
        }

        $member->delete();
    }

    /**
     * Filter members based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param array $query An associative array of query parameters to filter members.
     * @return Builder A query builder instance for the filtered members.
     */
    public function query(array $query)
    {
        return Member::filter($query ?? []);
    }
}