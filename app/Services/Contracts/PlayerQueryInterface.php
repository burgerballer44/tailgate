<?php

namespace App\Services\Contracts;

use App\Models\Member;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Retrieves player information within a member context and supports player management workflows.
 * Provides player lookups and retrieval of all players for a specific member in stable order,
 * supporting player roster and management features.
 */
interface PlayerQueryInterface
{
    public function query(array $query): Builder;

    public function getAllForMember(Member $member): Collection;
}
