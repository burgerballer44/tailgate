<?php

namespace App\ScoringPolicies;

use App\ScoringPolicies\Contracts\PredictionScoringPolicyOptionInterface;

/**
 * Alternative scoring policy metadata for placement-based scoring.
 */
final class PlacementPointsPolicy implements PredictionScoringPolicyOptionInterface
{
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
}
