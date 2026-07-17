<?php

namespace App\ScoringPolicies\Contracts;

use App\DTO\GamePointsContext;
use App\DTO\MissingPredictionContext;
use App\DTO\PlayerGamePointsResult;
use App\DTO\PlayerSeasonTotal;

/**
 * Defines the pluggable scoring strategy used by season result calculations.
 */
interface GroupPointsPolicyInterface
{
    /**
     * Unique persisted key for the points policy.
     */
    public static function key(): string;

    /**
     * Human-readable label used in admin policy selection UI.
     */
    public static function label(): string;

    /**
     * Human-readable description used in admin policy selection UI.
     */
    public static function description(): string;

    /**
     * Calculate points for a player prediction in a single game.
     */
    public function calculateGamePoints(GamePointsContext $context): PlayerGamePointsResult;

    /**
     * Assign points when a player has no submission for a game.
     */
    public function assignMissingPredictionPoints(MissingPredictionContext $context): PlayerGamePointsResult;

    /**
     * Compare two season totals for deterministic ranking.
     *
     * Return values should follow spaceship semantics: -1, 0, 1.
     */
    public function compareForRanking(PlayerSeasonTotal $left, PlayerSeasonTotal $right): int;
}
