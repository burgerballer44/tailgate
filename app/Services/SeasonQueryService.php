<?php

namespace App\Services;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Services\Contracts\SeasonQueryInterface;

class SeasonQueryService implements SeasonQueryInterface
{
    /**
     * Filter seasons based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter seasons.
     * @return Builder A query builder instance for the filtered seasons.
     */
    public function query(array $query): Builder
    {
        return Season::filter($query);
    }

    /**
     * Retrieve a season with its associated games and teams loaded.
     * This method is used to display detailed season information including games with home and away teams.
     *
     * @param  Season  $season  The season to load with relationships.
     * @return Season The season instance with games and teams loaded.
     */
    public function loadWithGames(Season $season): Season
    {
        return $season->load('games.homeTeam', 'games.awayTeam');
    }
}