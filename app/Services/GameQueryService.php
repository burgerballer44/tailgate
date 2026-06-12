<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Services\Contracts\GameQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Centralizes game retrieval for scheduling and scoring flows.
 * Supports filtering by season, teams, and start-time status while keeping query construction consistent.
 */
class GameQueryService implements GameQueryInterface
{
    /**
     * Builds a game query from supported filter inputs used by listing endpoints.
     *
     * @param  array  $filters  An associative array of query parameters to filter games.
     * @return Builder  A query builder instance for the filtered games.
     */
    public function query(array $filters): Builder
    {
        $query = Game::query();

        if (isset($filters['season_id'])) {
            $query->where('season_id', $filters['season_id']);
        }

        if (! empty($filters['home_team_id'])) {
            $query->where('home_team_id', $filters['home_team_id']);
        }

        if (! empty($filters['away_team_id'])) {
            $query->where('away_team_id', $filters['away_team_id']);
        }

        if (array_key_exists('start_time_tbd', $filters) && $filters['start_time_tbd'] !== '' && $filters['start_time_tbd'] !== null) {
            $query->where('start_time_tbd', filter_var($filters['start_time_tbd'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * Resolves eligible teams for a season so game forms only show valid matchups.
     *
     * @param  Season  $season  The season to get available teams for.
     * @return array  An associative array of team organizations keyed by team ID.
     */
    public function getAvailableTeamsForSeason(Season $season): array
    {
        return Team::whereHas('sports', function ($query) use ($season) {
            $query->where('sport', $season->sport);
        })->get()->pluck('organization', 'id')->toArray();
    }
}
