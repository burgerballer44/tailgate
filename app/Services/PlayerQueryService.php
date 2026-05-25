<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Player;
use App\Services\Contracts\PlayerQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PlayerQueryService implements PlayerQueryInterface
{
    /**
     * Filter players based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter players.
     * @return Builder A query builder instance for the filtered players.
     */
    public function query(array $query): Builder
    {
        return Player::filter($query ?? []);
    }

    /**
     * Get all players for a member in stable alphabetical order.
     */
    public function getAllForMember(Member $member): Collection
    {
        return $member->players()
            ->orderBy('player_name')
            ->get();
    }
}
