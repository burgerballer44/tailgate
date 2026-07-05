<?php

namespace App\Services\Contracts;

use App\Models\Group;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Defines read operations for members and memberships.
 *
 * Implementations provide filtered member queries and approved-membership
 * lookups for group and user contexts.
 */
interface MemberQueryInterface
{
    /**
     * Build a member query from supported filters.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter members.
     * @return Builder The filtered member query.
     */
    public function query(array $query): Builder;

    /**
     * Load approved members for a group with user and player-count context.
     *
     * @param  Group  $group  The group whose approved members should be loaded.
     * @return Collection<int, Member> Approved members ordered for roster display.
     */
    public function getApprovedMembersForGroup(Group $group): Collection;

    /**
     * Build a member query for a group.
     *
     * @param  Group  $group  The group whose members should be loaded.
     * @return Builder The member query scoped to the group.
     */
    public function getMembersForGroup(Group $group): Builder;

    /**
     * Resolve the approved member record for a specific group and user.
     *
     * @param  Group  $group  The group to inspect.
     * @param  User  $user  The user whose membership should be resolved.
     * @return Member The approved member record for the given user.
     */
    public function findApprovedMemberForGroupAndUser(Group $group, User $user): Member;

    /**
     * Load approved memberships for a user with relations needed by quick predictions.
     *
     * @param  User  $user  The user whose approved memberships should be loaded.
     * @return Collection<int, Member>
     */
    public function getApprovedMembershipsForUserWithQuickPredictionRelations(User $user): Collection;
}
