<?php

namespace App\Services;

use App\Models\Group;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Services\Contracts\GroupQueryInterface;

class GroupQueryService implements GroupQueryInterface
{
    /**
     * Filter groups based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param array $query An associative array of query parameters to filter groups.
     * @return Builder A query builder instance for the filtered groups.
     */
    public function query(array $query): Builder
    {
        $builder = Group::query();

        if ($q = $query['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(invite_code) LIKE LOWER(?)', ["%{$q}%"]);
            });
        }

        if (isset($query['owner_id'])) {
            $builder->where('owner_id', $query['owner_id']);
        }

        if (isset($query['name'])) {
            $builder->where('name', 'like', '%' . $query['name'] . '%');
        }

        return $builder;
    }

    /**
     * Find a group by its invite code.
     * This method retrieves a group using its unique invite code.
     *
     * @param string $inviteCode The invite code to search for.
     * @return Group|null The group instance if found, null otherwise.
     */
    public function findByInviteCode(string $inviteCode): ?Group
    {
        return Group::where('invite_code', $inviteCode)->first();
    }

    /**
     * Check if a user is already a member of a group.
     * This method determines if a specific user is already a member of the given group.
     *
     * @param Group $group The group to check membership in.
     * @param int $userId The ID of the user to check.
     * @return bool True if the user is already a member, false otherwise.
     */
    public function isUserAlreadyMember(Group $group, int $userId): bool
    {
        return $group->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if the group has reached its member limit.
     *
     * @param Group $group The group to check.
     * @return bool True if the member limit is reached, false otherwise.
     */
    public function isGroupMemberLimitReached(Group $group): bool
    {
        return $group->members()->count() >= $group->member_limit;
    }
}