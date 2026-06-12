<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Services\Contracts\UserQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Supports user discovery and user-context navigation across account and group features.
 * Provides consistent filtering and retrieval of groups a user owns or participates in.
 */
class UserQueryService implements UserQueryInterface
{
    /**
     * Builds a user query from supported filters for account administration views.
     *
     * @param  array  $query  An associative array of query parameters to filter users.
     * @return Builder  A query builder instance for the filtered users.
     */
    public function query(array $query): Builder
    {
        return User::filter($query);
    }

    /**
     * Loads groups visible to the user through ownership or membership relationships.
     *
     * @param  User  $user  The user to get accessible groups for.
     * @return Collection  The collection of accessible groups.
     */
    public function getAccessibleGroups(User $user): Collection
    {
        return Group::where('owner_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('owner')
            ->with(['members' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();
    }
}
