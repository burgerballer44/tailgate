<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Services\Contracts\UserQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserQueryService implements UserQueryInterface
{
    /**
     * Filter users based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter users.
     * @return Builder A query builder instance for the filtered users.
     */
    public function query(array $query): Builder
    {
        return User::filter($query);
    }

    /**
     * Get all groups the user is either an owner of or a member of (any status).
     *
     * @param  User  $user  The user to get accessible groups for.
     * @return Collection The collection of accessible groups.
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
