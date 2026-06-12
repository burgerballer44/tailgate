<?php

namespace App\Services\Contracts;

use App\Models\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Retrieves group information and validates group membership and resource constraints.
 * Supports searching groups by name or code, finding groups by invite code, and checking whether
 * members can be added based on membership and player limits.
 */
interface GroupQueryInterface
{
    public function query(array $query): Builder;

    public function findByInviteCode(string $inviteCode): ?Group;

    public function isUserAlreadyMember(Group $group, int $userId): bool;

    public function isGroupMemberLimitReached(Group $group): bool;
}
