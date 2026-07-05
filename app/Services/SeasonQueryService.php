<?php

namespace App\Services;

use App\Models\Season;
use App\Services\Contracts\SeasonQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Supports season discovery and season-detail retrieval.
 * Provides filtered season queries plus relationship loading for games and teams.
 */
class SeasonQueryService implements SeasonQueryInterface
{
    /**
     * Builds a season query from supported filters.
     *
     * @param  array  $query  An associative array of query parameters to filter seasons.
     * @return Builder A query builder instance for the filtered seasons.
     */
    public function query(array $query): Builder
    {
        // Delegate filter composition to the model-level filter scope.
        return Season::filter($query);
    }

    /**
     * Loads season game relationships.
     *
     * @param  Season  $season  The season to load with relationships.
     * @return Season The season instance with games and teams loaded.
     */
    public function loadWithGames(Season $season): Season
    {
        // Preload home/away team relations to avoid N+1 in consumers.
        return $season->load('games.homeTeam', 'games.awayTeam');
    }

    /**
     * Lists active seasons available for follow operations.
     */
    public function getAvailableSeasonsForFollow(): Collection
    {
        // Return only active seasons, newest naming first.
        return Season::query()
            ->where('active', true)
            ->orderByDesc('name')
            ->get();
    }
}
