<?php

namespace App\Services;

use App\Models\Group;
use App\Services\Contracts\GroupQueryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Powers group discovery and lookup workflows across listing and invite-based access.
 * Keeps filtering and relationship loading behavior consistent for group read operations.
 */
class GroupQueryService implements GroupQueryInterface
{
    /**
     * Builds a group query from supported filters for discovery and management views.
     *
    * @param array<string, mixed> $query Associative query parameters used to filter groups.
    * @return Builder The filtered group query with owner and follow context eager loaded.
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
            $builder->where('name', 'like', '%'.$query['name'].'%');
        }

        return $builder->with(['owner', 'follows.team']);
    }

    /**
     * Resolves a group from its invite code for join and validation flows.
     *
     * @param string $inviteCode The invite code to search for.
     * @return Group|null The matching group when found, or null when the code is invalid.
     */
    public function findByInviteCode(string $inviteCode): ?Group
    {
        return Group::query()->where('invite_code', $inviteCode)->first();
    }

    /**
     * Check if a user is already a member of a group.
     *
     * @param Group $group The group to inspect.
     * @param int $userId The user ID being checked.
     * @return bool True when the group's members relation contains the user.
     */
    public function isUserAlreadyMember(Group $group, int $userId): bool
    {
        return $group->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if the group has reached its member limit.
     *
     * @param Group $group The group to inspect.
     * @return bool True when the group member count meets or exceeds the configured limit.
     */
    public function isGroupMemberLimitReached(Group $group): bool
    {
        return $group->members()->count() >= $group->member_limit;
    }
}
