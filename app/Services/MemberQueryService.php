<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Services\Contracts\MemberQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Supports member retrieval for group administration, approvals, and roster views.
 * Provides consistent filtering and relationship loading for member-centric read paths.
 */
class MemberQueryService implements MemberQueryInterface
{
    /**
     * Builds a member query from supported filters used by group administration views.
     *
     * @param  array  $query  An associative array of query parameters to filter members.
     * @return Builder  A query builder instance for the filtered members.
     */
    public function query(array $query): Builder
    {
        $builder = Member::query();

        if (isset($query['user_id'])) {
            $builder->where('user_id', $query['user_id']);
        }

        if (isset($query['group_id'])) {
            $builder->where('group_id', $query['group_id']);
        }

        if (isset($query['status'])) {
            $builder->where('status', $query['status']);
        }

        return $builder;
    }

    /**
     * Loads approved members with user and player-count context for roster screens.
     */
    public function getApprovedMembersForGroup(Group $group): Collection
    {
        return $group->members()
            ->where('status', MemberStatus::APPROVED->value)
            ->with(['user'])
            ->withCount('players')
            ->orderBy('user_id')
            ->get();
    }

    /**
     * Resolve the approved member record for a specific group and user.
     */
    public function findApprovedMemberForGroupAndUser(Group $group, User $user): Member
    {
        return $group->members()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::APPROVED->value)
            ->firstOrFail();
    }
}
