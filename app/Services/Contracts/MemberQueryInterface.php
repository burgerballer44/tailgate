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
    public function query(array $query): Builder;

    public function getApprovedMembersForGroup(Group $group): Collection;

    public function findApprovedMemberForGroupAndUser(Group $group, User $user): Member;
}
