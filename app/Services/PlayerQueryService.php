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
     * Builds a player query from supported filters used by roster interfaces.
     *
     * @param array<string, mixed> $query Associative query parameters used to filter players.
     * @return Builder The filtered player query.
     */
    public function query(array $query): Builder
    {
        return Player::filter($query ?? []);
    }

    /**
     * Lists a member's players in stable alphabetical order for predictable UI rendering.
     *
     * @param Member $member The member whose players should be loaded.
     * @return Collection<int, Player> The player's roster ordered alphabetically by name.
     */
    public function getAllForMember(Member $member): Collection
    {
        return $member->players()
            ->orderBy('player_name')
            ->get();
    }
}
