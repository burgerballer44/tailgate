<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedMemberData;
use App\Models\Group;
use App\Models\Member;

/**
 * Manages the lifecycle of group memberships and member state changes.
 * Handles adding members to groups, updating member roles and approval status, and removing members,
 * supporting group membership administration and approval workflows.
 */
interface MemberCommandInterface
{
    /**
     * Create a new member within the given group.
     *
     * @param Group $group The group to add the member to.
     * @param ValidatedMemberData $data The normalized membership payload.
     * @return Member The created member instance.
     */
    public function createForGroup(Group $group, ValidatedMemberData $data): Member;

    /**
     * Update an existing member.
     *
     * @param Member $member The member to update.
     * @param ValidatedMemberData $data The normalized membership payload.
     * @return Member The updated member instance.
     */
    public function update(Member $member, ValidatedMemberData $data): Member;

    /**
     * Delete a member.
     *
     * @param Member $member The member to delete.
     * @return void
     */
    public function delete(Member $member): void;
}
