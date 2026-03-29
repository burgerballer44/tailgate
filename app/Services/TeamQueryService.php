<?php

namespace App\Services;

use App\Models\Team;
use App\Services\Contracts\TeamQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;

class TeamQueryService implements TeamQueryInterface
{
    /**
     * Filter teams based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter teams.
     * @return Builder A query builder instance for the filtered teams.
     */
    public function query(array $query): Builder
    {
        return Team::filter($query)->with('sports');
    }
}
