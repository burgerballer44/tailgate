<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedMemberData;
use App\Models\Group;
use App\Models\Member;

/**
 * Defines write operations for group memberships.
 *
 * Implementations create, update, and remove members while enforcing
 * membership-related domain rules.
 */
interface MemberCommandInterface
{
    /**
     * Create a new member within the given group.
     *
     * @param  Group  $group  The group to add the member to.
     * @param  ValidatedMemberData  $data  The normalized membership payload.
     * @return Member The created member instance.
     */
    public function createForGroup(Group $group, ValidatedMemberData $data): Member;

    /**
     * Update an existing member.
     *
     * @param  Member  $member  The member to update.
     * @param  ValidatedMemberData  $data  The normalized membership payload.
     * @return Member The updated member instance.
     */
    public function update(Member $member, ValidatedMemberData $data): Member;

    /**
     * Reject a pending join request while preserving membership history.
     *
     * @param  Member  $member  The pending membership to reject.
     */
    public function reject(Member $member): void;

    /**
     * Remove a member through an admin action while preserving historical data.
     *
     * @param  Member  $member  The membership to remove.
     */
    public function remove(Member $member): void;

    /**
     * Mark a member as left when they voluntarily leave the group.
     *
     * @param  Member  $member  The membership that is leaving.
     */
    public function leave(Member $member): void;
}
