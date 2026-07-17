<?php

namespace App\ScoringPolicies\Concerns;

use App\DTO\PlayerSeasonTotal;

/**
 * Provides deterministic, config-driven ranking tie-break behavior shared by scoring policies.
 */
trait DeterministicRankingComparison
{
    /**
     * Compare two season totals using configured tie-breakers and deterministic fallback.
     */
    private function compareSeasonTotalsWithTieBreakers(
        PlayerSeasonTotal $left,
        PlayerSeasonTotal $right,
        string $policyKey,
    ): int {
        $byPoints = $left->totalPoints <=> $right->totalPoints;

        if ($byPoints !== 0) {
            return $byPoints;
        }

        $tieBreakers = config('prediction_results.ranking.'.$policyKey.'.tie_breakers', []);

        foreach ($tieBreakers as $tieBreaker) {
            if ($tieBreaker === 'previous_week_rank_asc') {
                $leftPrevious = $left->previousRank ?? PHP_INT_MAX;
                $rightPrevious = $right->previousRank ?? PHP_INT_MAX;

                $comparison = $leftPrevious <=> $rightPrevious;

                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            if ($tieBreaker === 'player_id_asc') {
                $comparison = $left->playerId <=> $right->playerId;

                if ($comparison !== 0) {
                    return $comparison;
                }
            }
        }

        // Final deterministic fallback when no configured tie-breaker separates the rows.
        return $left->playerId <=> $right->playerId;
    }
}
