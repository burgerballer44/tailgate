<?php

namespace App\Services;

use App\DTO\ValidatedMemberData;
use App\Models\Enums\GroupRole;
use App\Models\Enums\GroupThresholdRule;
use App\Models\Enums\MemberStatus;
use App\Models\Group;
use App\Models\Member;
use App\Services\Contracts\MemberCommandInterface;
use DomainException;
use Illuminate\Support\Facades\Schema;

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
     * @param  ValidatedMemberData  $data  Validated member data including the user and optional role/status.
     * @return Member The created member instance.
     */
    public function createForGroup(Group $group, ValidatedMemberData $data): Member
    {
        // Normalize optional role/status to domain defaults before persisting.
        $memberData = [
            'user_id' => $data->user_id,
            'role' => $data->role?->value ?? GroupRole::GROUP_MEMBER->value,
            'status' => $data->status?->value ?? MemberStatus::APPROVED->value,
        ];

        // Create the member through the group relation for automatic scoping.
        return $group->members()->create($memberData);
    }

    /**
     * Applies role and status changes to an existing member.
     *
     * @param  Member  $member  The member to update.
     * @param  ValidatedMemberData  $data  Validated data to apply to the member.
     * @return Member The updated member instance.
     */
    public function update(Member $member, ValidatedMemberData $data): Member
    {
        // Build a partial payload so only intended fields are changed.
        $updateData = [];

        if ($data->role !== null) {
            $updateData['role'] = $data->role->value;
        }

        if ($data->status !== null) {
            $updateData['status'] = $data->status->value;
        }

        // Persist role/status transitions in one operation.
        $member->fill($updateData);
        $member->save();

        return $member;
    }

    /**
     * Reject a pending membership request.
     */
    public function reject(Member $member): void
    {
        if ($member->status !== MemberStatus::PENDING->value) {
            throw new DomainException('Only pending memberships can be rejected.');
        }

        $member->status = MemberStatus::REJECTED->value;

        if (Schema::hasColumn($member->getTable(), 'left_at')) {
            $member->left_at = now();
        }

        $member->save();
    }

    /**
     * Remove a member through admin action while preserving historical data.
     *
     * @throws DomainException If removing would violate admin minimum requirements.
     */
    public function remove(Member $member): void
    {
        $this->assertAdminMinimumNotViolated($member);
        $this->deactivateMember($member, MemberStatus::REMOVED);
    }

    /**
     * Mark a member as left when they voluntarily leave the group.
     *
     * @throws DomainException If leaving would violate admin minimum requirements.
     */
    public function leave(Member $member): void
    {
        $this->assertAdminMinimumNotViolated($member);
        $this->deactivateMember($member, MemberStatus::LEFT);
    }

    /**
     * Enforce admin-minimum safety before deactivating an admin membership.
     *
     * @throws DomainException If the group would lose its last admin.
     */
    private function assertAdminMinimumNotViolated(Member $member): void
    {
        $group = $member->group;

        if (
            $group->admin->count() == GroupThresholdRule::MIN_NUMBER_ADMINS->value() &&
            $group->admin->first()->id == $member->id
        ) {
            throw new DomainException('Group admin minimum reached. Please update a different member to the Group Admin role before removing this member.');
        }
    }

    /**
     * Apply a terminal membership status and leave timestamp.
     */
    private function deactivateMember(Member $member, MemberStatus $terminalStatus): void
    {
        if ($member->status === MemberStatus::PENDING->value) {
            throw new DomainException('Pending memberships must be explicitly approved or rejected.');
        }

        $member->status = $terminalStatus->value;

        if (Schema::hasColumn($member->getTable(), 'left_at')) {
            $member->left_at = now();
        }

        $member->save();
    }
}
