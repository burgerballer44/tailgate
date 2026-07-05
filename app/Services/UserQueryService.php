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
     * @param  array<string, mixed>  $query  Associative query parameters used to filter users.
     * @return Builder The filtered user query.
     */
    public function query(array $query): Builder
    {
        // Delegate user filtering to the model's query scope.
        return User::filter($query);
    }

    /**
     * Loads groups visible to the user through ownership or membership relationships.
     *
     * @param  User  $user  The user whose accessible groups should be loaded.
     * @return Collection<int, Group> Groups owned by or shared with the user, with ownership and matching membership loaded.
     */
    public function getAccessibleGroups(User $user): Collection
    {
        // A group is accessible when the user owns it or is one of its members.
        return Group::query()
            ->where(function (Builder $groupQuery) use ($user): void {
                $groupQuery
                    ->where('owner_id', $user->id)
                    ->orWhereHas('members', function (Builder $memberQuery) use ($user): void {
                        $memberQuery->where('user_id', $user->id);
                    });
            })
            // Load owner and only the membership rows relevant to the given user.
            ->with('owner')
            ->with(['members' => function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            }])
            ->get();
    }
}
