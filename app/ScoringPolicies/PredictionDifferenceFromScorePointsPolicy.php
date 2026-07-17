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
 * Default scoring policy metadata for prediction difference from final score.
 */
final class PredictionDifferenceFromScorePointsPolicy implements GroupPointsPolicyInterface, PredictionScoringPolicyOptionInterface
{
    use DeterministicRankingComparison;

    public static function key(): string
    {
        return 'prediction-difference-from-score';
    }

    public static function label(): string
    {
        return 'Prediction difference from score (lowest total wins)';
    }

    public static function description(): string
    {
        return 'Each game result is the sum of absolute differences between submitted predictions and final game scores, plus any penalties.';
    }

    /**
     * Calculate game points using absolute score differences plus penalties.
     */
    public function calculateGamePoints(GamePointsContext $context): PlayerGamePointsResult
    {
        if ($context->predictedFollowedScore === null || $context->predictedOpponentScore === null) {
            return $this->assignMissingPredictionPoints(new MissingPredictionContext(
                gameId: $context->gameId,
                playerId: $context->playerId,
                worstSubmittedGamePoints: null,
                fallbackPoints: (float) config('prediction_results.missing_prediction.'.self::key().'.no_submissions_fallback_points', 14),
            ));
        }

        $followedDifference = abs($context->predictedFollowedScore - $context->actualFollowedScore);
        $opponentDifference = abs($context->predictedOpponentScore - $context->actualOpponentScore);
        $basePoints = $followedDifference + $opponentDifference;
        $gamePoints = $basePoints + $context->penaltyPoints;

        return new PlayerGamePointsResult(
            playerId: $context->playerId,
            gameId: $context->gameId,
            points: (float) $gamePoints,
            penaltyPoints: $context->penaltyPoints,
            calculationNotes: [
                'diff-followed='.$followedDifference,
                'diff-opponent='.$opponentDifference,
                'base='.$basePoints,
                'penalties='.$context->penaltyPoints,
            ],
        );
    }

    /**
     * Assign missing prediction points as worst submitted score + offset, with fallback baseline.
     */
    public function assignMissingPredictionPoints(MissingPredictionContext $context): PlayerGamePointsResult
    {
        $offset = (float) config('prediction_results.missing_prediction.'.self::key().'.submitted_game_points_offset', 7);
        $fallback = (float) config('prediction_results.missing_prediction.'.self::key().'.no_submissions_fallback_points', 14);

        $points = $context->worstSubmittedGamePoints !== null
            ? $context->worstSubmittedGamePoints + $offset
            : $fallback;

        return new PlayerGamePointsResult(
            playerId: $context->playerId,
            gameId: $context->gameId,
            points: $points,
            penaltyPoints: 0,
            calculationNotes: [
                'missing-prediction=true',
                'worst-submitted='.(string) ($context->worstSubmittedGamePoints ?? 'none'),
                'offset='.$offset,
                'fallback='.$fallback,
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
