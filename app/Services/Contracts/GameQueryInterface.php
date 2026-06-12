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
    public function query(array $filters): Builder;

    public function getAvailableTeamsForSeason(Season $season): array;
}
