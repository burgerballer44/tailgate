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
    /**
     * Build a group query from supported filters.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter groups.
     * @return Builder The filtered group query.
     */
    public function query(array $query): Builder;

    /**
     * Resolve a group by invite code for join and validation flows.
     *
     * @param string $inviteCode The invite code to search for.
     * @return Group|null The matching group when found, or null when no group matches.
     */
    public function findByInviteCode(string $inviteCode): ?Group;

    /**
     * Determine whether a user already belongs to a group.
     *
     * @param Group $group The group to inspect.
     * @param int $userId The user ID being checked.
     * @return bool True when the user already has a membership record in the group.
     */
    public function isUserAlreadyMember(Group $group, int $userId): bool;

    /**
     * Determine whether the group has reached its member capacity.
     *
     * @param Group $group The group to inspect.
     * @return bool True when the current member count meets or exceeds the limit.
     */
    public function isGroupMemberLimitReached(Group $group): bool;
}
