<?php

namespace App\ScoringPolicies\Contracts;

/**
 * Defines static metadata required to present scoring policies in the UI.
 */
interface PredictionScoringPolicyOptionInterface
{
    /**
     * Unique persisted key for the scoring policy.
     */
    public static function key(): string;

    /**
     * Admin-facing short label for radio option rendering.
     */
    public static function label(): string;

    /**
     * Admin-facing description explaining how scoring is calculated.
     */
    public static function description(): string;
}
