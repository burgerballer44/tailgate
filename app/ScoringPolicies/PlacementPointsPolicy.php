<?php

namespace App\ScoringPolicies;

use App\DTO\GamePointsContext;
use App\DTO\MissingPredictionContext;
use App\DTO\PlayerGamePointsResult;
use App\DTO\PlayerSeasonTotal;
use App\ScoringPolicies\Concerns\DeterministicRankingComparison;
use App\ScoringPolicies\Contracts\GroupPointsPolicyInterface;
use App\ScoringPolicies\Contracts\PredictionScoringPolicyOptionInterface;

/**
 * Alternative scoring policy metadata for placement-based scoring.
 */
final class PlacementPointsPolicy implements GroupPointsPolicyInterface, PredictionScoringPolicyOptionInterface
{
    use DeterministicRankingComparison;

    public static function key(): string
    {
        return 'placement-points';
    }

    public static function label(): string
    {
        return 'Placement points (1st, 2nd, 3rd...)';
    }

    public static function description(): string
    {
        return 'Players are ranked each game by prediction difference from score and awarded placement points; lower season totals rank higher.';
    }

    /**
     * Calculate game points using precomputed placement rank.
     */
    public function calculateGamePoints(GamePointsContext $context): PlayerGamePointsResult
    {
        if ($context->placementRank !== null) {
            return new PlayerGamePointsResult(
                playerId: $context->playerId,
                gameId: $context->gameId,
                points: (float) $context->placementRank,
                penaltyPoints: $context->penaltyPoints,
                calculationNotes: [
                    'placement-rank='.$context->placementRank,
                ],
            );
        }

        // If placement rank is not provided yet, fall back to a deterministic approximation
        // based on prediction difference to keep this method safe for intermediate callers.
        if ($context->predictedFollowedScore === null || $context->predictedOpponentScore === null) {
            return $this->assignMissingPredictionPoints(new MissingPredictionContext(
                gameId: $context->gameId,
                playerId: $context->playerId,
                worstSubmittedGamePoints: null,
                fallbackPoints: (float) config('prediction_results.missing_prediction.'.PredictionDifferenceFromScorePointsPolicy::key().'.no_submissions_fallback_points', 14),
            ));
        }

        $difference = abs($context->predictedFollowedScore - $context->actualFollowedScore)
            + abs($context->predictedOpponentScore - $context->actualOpponentScore);

        return new PlayerGamePointsResult(
            playerId: $context->playerId,
            gameId: $context->gameId,
            points: (float) $difference,
            penaltyPoints: $context->penaltyPoints,
            calculationNotes: [
                'placement-rank=unavailable',
                'fallback-difference='.$difference,
            ],
        );
    }

    /**
     * Assign missing prediction placement points with deterministic trailing behavior.
     */
    public function assignMissingPredictionPoints(MissingPredictionContext $context): PlayerGamePointsResult
    {
        $countsAsLastPlace = (bool) config('prediction_results.missing_prediction.'.self::key().'.missing_prediction_counts_as_last_place', true);
        $fallback = (float) config('prediction_results.missing_prediction.'.PredictionDifferenceFromScorePointsPolicy::key().'.no_submissions_fallback_points', 14);

        $points = $countsAsLastPlace
            ? ($context->worstSubmittedGamePoints !== null ? $context->worstSubmittedGamePoints + 1.0 : $fallback)
            : $fallback;

        return new PlayerGamePointsResult(
            playerId: $context->playerId,
            gameId: $context->gameId,
            points: $points,
            penaltyPoints: 0,
            calculationNotes: [
                'missing-prediction=true',
                'counts-as-last-place='.(string) ($countsAsLastPlace ? 'true' : 'false'),
            ],
        );
    }

    /**
     * Compare two season totals using config-defined tie-breakers and deterministic fallback.
     */
    public function compareForRanking(PlayerSeasonTotal $left, PlayerSeasonTotal $right): int
    {
        return $this->compareSeasonTotalsWithTieBreakers($left, $right, self::key());
    }
}
