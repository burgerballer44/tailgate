<?php

namespace App\Services\Contracts;

use App\DTO\SeasonResultsViewData;

/**
 * Builds season-scoped leaderboard and raw prediction results for a group.
 */
interface GroupSeasonLeaderboardServiceInterface
{
    /**
     * Build leaderboard and raw game scoring data for a group-season context.
     */
    public function buildSeasonResults(int $groupId, int $seasonId, ?int $asOfGameId = null): SeasonResultsViewData;
}
