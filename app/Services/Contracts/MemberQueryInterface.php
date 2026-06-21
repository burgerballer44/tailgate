<?php

namespace App\Services\Contracts;

use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retrieves member information and filters approved members within a group context.
 * Provides member lookups, approved member listings with player counts, and member-user associations
 * to support group membership management and member-specific features.
 */
interface MemberQueryInterface
{
    /**
     * Build a member query from supported filters.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter members.
     * @return Builder The filtered member query.
     */
    public function query(array $query): Builder;

    /**
     * Load approved members for a group with user and player-count context.
     *
     * @param Group $group The group whose approved members should be loaded.
     * @return Collection<int, \App\Models\Member> Approved members ordered for roster display.
     */
    public function getApprovedMembersForGroup(Group $group): Collection;

    /**
     * Resolve the approved member record for a specific group and user.
     *
     * @param Group $group The group to inspect.
     * @param User $user The user whose membership should be resolved.
     * @return Member The approved member record for the given user.
     */
    public function findApprovedMemberForGroupAndUser(Group $group, User $user): Member;
}
