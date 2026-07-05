<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Player;
use App\Services\Contracts\PlayerQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Supports player retrieval for roster views and member-level player management.
 * Provides stable filtering and ordering behavior for player read operations.
 */
class PlayerQueryService implements PlayerQueryInterface
{
    /**
     * Builds a player query from supported filters.
     *
     * @param  array<string, mixed>  $query  Associative query parameters used to filter players.
     * @return Builder The filtered player query.
     */
    public function query(array $query): Builder
    {
        // Delegate filter composition to the model scope for a single filtering contract.
        return Player::filter($query ?? []);
    }

    /**
     * Lists a member's players in stable alphabetical order.
     *
     * @param  Member  $member  The member whose players should be loaded.
     * @return Collection<int, Player> The player's roster ordered alphabetically by name.
     */
    public function getAllForMember(Member $member): Collection
    {
        // Deterministic ordering avoids inconsistent roster presentation and tests.
        return $member->players()
            ->orderBy('player_name')
            ->get();
    }

    /**
     * Build a player query for a member.
     *
     * @param  Member  $member  The member whose players should be loaded.
     * @return Builder The player query scoped to the member.
     */
    public function getPlayersForMember(Member $member): Builder
    {
        // Expose a scoped builder so callers can paginate or further filter.
        return $member->players()
            ->orderBy('player_name');
    }
}
