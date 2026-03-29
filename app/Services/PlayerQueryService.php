<?php

namespace App\Services;

use App\Models\Player;
use App\Services\Contracts\PlayerQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;

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
}
