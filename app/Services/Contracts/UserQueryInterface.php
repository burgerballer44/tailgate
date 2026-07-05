<?php

namespace App\Services\Contracts;

use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Defines read operations for users and user-accessible groups.
 */
interface UserQueryInterface
{
    /**
     * Build a user query from supported filters.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter users.
     * @return Builder The filtered user query.
     */
    public function query(array $query): Builder;

    /**
     * Load groups visible to a user through ownership or membership.
     *
     * @param  User  $user  The user whose accessible groups should be loaded.
     * @return Collection<int, Group> Groups owned by or shared with the user.
     */
    public function getAccessibleGroups(User $user): Collection;
}
