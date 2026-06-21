<?php

namespace App\Services\Contracts;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * Retrieves game information with flexible filtering and relationship loading.
 * Supports querying by season, teams, and start time status to power game listings and scheduling features.
 * Also provides the list of teams available for a given season based on that season's sport.
 */
interface GameQueryInterface
{
    /**
     * Build a game query from supported filters.
     *
     * @param array<string, mixed> $filters Associative query parameters used to filter games.
     * @return Builder The filtered game query.
     */
    public function query(array $filters): Builder;

    /**
     * Resolve teams eligible for a season's game forms.
     *
     * @param Season $season The season whose sport determines eligible teams.
     * @return array<int, string> Team organizations keyed by team ID for form selection.
     */
    public function getAvailableTeamsForSeason(Season $season): array;
}
