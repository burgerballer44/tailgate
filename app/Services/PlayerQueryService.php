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
     * @param  array  $query  An associative array of query parameters to filter players.
     * @return Builder  A query builder instance for the filtered players.
     */
    public function query(array $query): Builder
    {
        return Player::filter($query ?? []);
    }

    /**
     * Lists a member's players in stable alphabetical order for predictable UI rendering.
     */
    public function getAllForMember(Member $member): Collection
    {
        return $member->players()
            ->orderBy('player_name')
            ->get();
    }
}
