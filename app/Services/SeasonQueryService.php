<?php

namespace App\Services;

use App\Models\Season;
use App\Services\Contracts\SeasonQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Supports season discovery and season-detail retrieval for scheduling flows.
 * Provides filtered season queries plus relationship loading for games and teams.
 */
class SeasonQueryService implements SeasonQueryInterface
{
    /**
     * Builds a season query from supported filters for browsing and management views.
     *
     * @param  array  $query  An associative array of query parameters to filter seasons.
     * @return Builder  A query builder instance for the filtered seasons.
     */
    public function query(array $query): Builder
    {
        return Season::filter($query);
    }

    /**
     * Loads season game relationships needed for detailed schedule views.
     *
     * @param  Season  $season  The season to load with relationships.
     * @return Season  The season instance with games and teams loaded.
     */
    public function loadWithGames(Season $season): Season
    {
        return $season->load('games.homeTeam', 'games.awayTeam');
    }

    /**
     * Lists active seasons offered in follow-team selection workflows.
     */
    public function getAvailableSeasonsForFollow(): Collection
    {
        return Season::query()
            ->where('active', true)
            ->orderByDesc('name')
            ->get();
    }
}
