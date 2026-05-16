<?php

namespace App\Services;

use App\Models\Season;
use App\Services\Contracts\SeasonQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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

    /**
     * Get active seasons available for the follow-team form.
     */
    public function getAvailableSeasonsForFollow(): Collection
    {
        return Season::query()
            ->where('active', true)
            ->orderByDesc('name')
            ->get();
    }
}
