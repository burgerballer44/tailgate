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
    public function query(array $query): Builder;

    public function getAccessibleGroups(User $user): Collection;
}
