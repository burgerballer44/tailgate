<?php

namespace App\Services;

use App\Models\Group;
use App\Models\MemberStatus;
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
     * @param  array<string, mixed>  $query  Associative query parameters used to filter groups.
     * @return Builder The filtered group query with owner and follow context eager loaded.
     */
    public function query(array $query): Builder
    {
        // Start from the base group query and layer optional filters.
        $builder = Group::query();

        if ($q = $query['q'] ?? null) {
            // Match free-text against both name and invite code.
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(invite_code) LIKE LOWER(?)', ["%{$q}%"]);
            });
        }

        if (isset($query['owner_id'])) {
            // Restrict results to groups owned by a specific user.
            $builder->where('owner_id', $query['owner_id']);
        }

        if (isset($query['name'])) {
            // Preserve explicit name filtering for narrower searches.
            $builder->where('name', 'like', '%'.$query['name'].'%');
        }

        // Eager-load common read-path relations to avoid N+1 in list endpoints.
        return $builder->with(['owner', 'follows.team']);
    }

    /**
     * Resolves a group from its invite code for join and validation flows.
     *
     * @param  string  $inviteCode  The invite code to search for.
     * @return Group|null The matching group when found, or null when the code is invalid.
     */
    public function findByInviteCode(string $inviteCode): ?Group
    {
        // Invite codes are expected to be unique, so first() is sufficient.
        return Group::query()->where('invite_code', $inviteCode)->first();
    }

    /**
     * Check if a user is already a member of a group.
     *
     * @param  Group  $group  The group to inspect.
     * @param  int  $userId  The user ID being checked.
     * @return bool True when the group's members relation contains the user.
     */
    public function isUserAlreadyMember(Group $group, int $userId): bool
    {
        // Membership existence check avoids loading an entire members collection.
        return $group->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if the group has reached its member limit.
     *
     * @param  Group  $group  The group to inspect.
     * @return bool True when the group member count meets or exceeds the configured limit.
     */
    public function isGroupMemberLimitReached(Group $group): bool
    {
        // Ex-members are retained for history and should not consume active capacity.
        return $group->members()
            ->whereIn('status', [MemberStatus::APPROVED->value, MemberStatus::PENDING->value])
            ->count() >= $group->member_limit;
    }
}
