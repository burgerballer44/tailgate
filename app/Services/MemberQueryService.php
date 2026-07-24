<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Member;
use App\Models\Enums\MemberStatus;
use App\Models\User;
use App\Services\Contracts\MemberQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Supports member retrieval for group administration, approvals, and roster views.
 * Provides consistent filtering and relationship loading for member-centric read paths.
 */
class MemberQueryService implements MemberQueryInterface
{
    /**
     * Builds a member query from supported filters used by group administration views.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter members.
     * @return Builder The filtered member query.
     */
    public function query(array $query): Builder
    {
        // Start from the base member query and append optional filters.
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

        // Return the composed builder so callers can add pagination/sorting.
        return $builder;
    }

    /**
     * Loads approved members with user and player-count context for roster screens.
     *
     * @param  Group  $group  The group whose approved members should be loaded.
     * @return Collection<int, Member> Approved members ordered by user ID with user and player counts loaded.
     */
    public function getApprovedMembersForGroup(Group $group): Collection
    {
        // Restrict to approved members and preload user/player counts for roster displays.
        return $group->members()
            ->where('status', MemberStatus::APPROVED->value)
            ->with(['user'])
            ->withCount('players')
            ->orderBy('user_id')
            ->get();
    }

    /**
     * Build a member query for a group.
     *
     * @param  Group  $group  The group whose members should be loaded.
     * @return Builder The member query scoped to the group.
     */
    public function getMembersForGroup(Group $group): Builder
    {
        // Keep this as a builder to allow callers to apply their own projections.
        return $group->members()->with('user');
    }

    /**
     * Resolve the approved member record for a specific group and user.
     *
     * @param  Group  $group  The group to inspect.
     * @param  User  $user  The user whose approved membership should be resolved.
     * @return Member The approved member record for the given user.
     *
     * @throws ModelNotFoundException If the user is not an approved member of the group.
     */
    public function findApprovedMemberForGroupAndUser(Group $group, User $user): Member
    {
        // Enforce approved status in the query so callers cannot bypass membership rules.
        return $group->members()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::APPROVED->value)
            ->firstOrFail();
    }

    /**
     * Load approved memberships for a user with quick-prediction relations eager loaded.
     *
     * @param  User  $user  The user whose approved memberships should be loaded.
     * @return Collection<int, Member>
     */
    public function getApprovedMembershipsForUserWithQuickPredictionRelations(User $user): Collection
    {
        // Load only approved memberships with relations needed by quick prediction flows.
        return Member::query()
            ->where('user_id', $user->id)
            ->where('status', MemberStatus::APPROVED->value)
            ->with([
                'group.follows.team',
                'players',
            ])
            ->get();
    }
}
