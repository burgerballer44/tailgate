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
    public function createForGroup(Group $group, ValidatedMemberData $data): Member;

    public function update(Member $member, ValidatedMemberData $data): Member;

    public function delete(Member $member): void;
}
