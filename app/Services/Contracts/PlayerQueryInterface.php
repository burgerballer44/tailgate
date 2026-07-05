<?php

namespace App\Services\Contracts;

use App\Models\Member;
use App\Models\Player;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Defines read operations for player records.
 *
 * Implementations provide filtered queries and member-scoped player retrieval
 * in a stable order.
 */
interface PlayerQueryInterface
{
    /**
     * Build a player query from supported filters.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter players.
     * @return Builder The filtered player query.
     */
    public function query(array $query): Builder;

    /**
     * Load all players for a member in stable display order.
     *
     * @param  Member  $member  The member whose players should be loaded.
     * @return Collection<int, Player> The member's roster ordered alphabetically.
     */
    public function getAllForMember(Member $member): Collection;

    /**
     * Build a player query for a member.
     *
     * @param  Member  $member  The member whose players should be loaded.
     * @return Builder The player query scoped to the member.
     */
    public function getPlayersForMember(Member $member): Builder;
}
