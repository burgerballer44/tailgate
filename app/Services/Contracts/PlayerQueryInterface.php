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
    /**
     * Build a player query from supported filters.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter players.
     * @return Builder The filtered player query.
     */
    public function query(array $query): Builder;

    /**
     * Load all players for a member in stable display order.
     *
     * @param Member $member The member whose players should be loaded.
     * @return Collection<int, \App\Models\Player> The member's roster ordered alphabetically.
     */
    public function getAllForMember(Member $member): Collection;
}
