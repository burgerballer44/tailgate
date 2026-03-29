<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Services\Contracts\GameQueryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;

class GameQueryService implements GameQueryInterface
{
    /**
     * Filter games based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $filters  An associative array of query parameters to filter games.
     * @return Builder A query builder instance for the filtered games.
     */
    public function query(array $filters): Builder
    {
        $query = Game::query();

        if (isset($filters['season_id'])) {
            $query->where('season_id', $filters['season_id']);
        }

        return $query;
    }

    /**
     * Get available teams for a season based on the season's sport.
     * This method retrieves teams that participate in the same sport as the season, used for game creation and editing.
     *
     * @param  Season  $season  The season to get available teams for.
     * @return array An associative array of team organizations keyed by team ID.
     */
    public function getAvailableTeamsForSeason(Season $season): array
    {
        return Team::whereHas('sports', function ($query) use ($season) {
            $query->where('sport', $season->sport);
        })->get()->pluck('organization', 'id')->toArray();
    }
}
