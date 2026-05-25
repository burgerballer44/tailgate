<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Member;
use App\Models\MemberStatus;
use App\Models\User;
use App\Services\Contracts\MemberQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MemberQueryService implements MemberQueryInterface
{
    /**
     * Filter members based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter members.
     * @return Builder A query builder instance for the filtered members.
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
     * Get all approved members for a group with user and player count data.
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
