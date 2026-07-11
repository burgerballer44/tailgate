<?php

namespace App\ScoringPolicies;

use App\ScoringPolicies\Contracts\PredictionScoringPolicyOptionInterface;

/**
 * Default scoring policy metadata for prediction difference from final score.
 */
final class PredictionDifferenceFromScorePointsPolicy implements PredictionScoringPolicyOptionInterface
{
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
}
