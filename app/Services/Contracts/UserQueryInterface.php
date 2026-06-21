<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retrieves user information and their accessible group affiliations.
 * Provides user lookups and retrieval of groups the user owns or is a member of (any status),
 * supporting user profile pages and group navigation features.
 */
interface UserQueryInterface
{
    /**
     * Build a user query from supported filters.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter users.
     * @return Builder The filtered user query.
     */
    public function query(array $query): Builder;

    /**
     * Load groups visible to a user through ownership or membership.
     *
     * @param User $user The user whose accessible groups should be loaded.
     * @return Collection<int, \App\Models\Group> Groups owned by or shared with the user.
     */
    public function getAccessibleGroups(User $user): Collection;
}
