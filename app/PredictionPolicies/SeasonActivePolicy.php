<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Requires the game to belong to an active season before a prediction is accepted.
 * This is an app-level policy applied to every submission.
 */
class SeasonActivePolicy implements PredictionPolicyRuleInterface
{
    /**
     * Returns the stable policy key used for configuration and display.
     */
    public function key(): string
    {
        return 'season-active';
    }

    /**
     * Returns the short label used in UI and violation summaries.
     */
    public function label(): string
    {
        return 'Season active';
    }

    /**
     * Explains the business rule enforced by this policy.
     */
    public function description(): string
    {
        return 'Predictions can only be submitted for games in active seasons.';
    }

    /**
     * Identifies this rule as an app-level policy.
     */
    public function scope(): PredictionPolicyScope
    {
        return PredictionPolicyScope::APP;
    }

    /**
     * Returns true when the related season exists and is active.
     */
    public function passes(PredictionPolicyContext $context): bool
    {
        return (bool) $context->game->season?->active;
    }
}