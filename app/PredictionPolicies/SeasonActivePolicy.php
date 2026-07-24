<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\Enums\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Requires the target game to belong to an active season.
 *
 * This app-level policy is enforced for every prediction submission and update.
 */
class SeasonActivePolicy implements PredictionPolicyRuleInterface
{
    /**
     * Returns the stable policy key used for configuration and display.
     *
     * @return string Unique machine-readable key used for policy configuration and violation records.
     */
    public function key(): string
    {
        return 'season-active';
    }

    /**
     * Returns the short label used in violation summaries.
     *
     * @return string Human-readable name displayed in violation messages and policy management screens.
     */
    public function label(): string
    {
        return 'Season active';
    }

    /**
     * Explains the business rule enforced by this policy.
     *
     * @return string Human-readable rule description.
     */
    public function description(): string
    {
        return 'Predictions can only be submitted for games in active seasons.';
    }

    /**
     * Identifies this rule as an app-level policy.
     *
     * @return PredictionPolicyScope Indicates whether this policy is enforced globally or only when enabled per group.
     */
    public function scope(): PredictionPolicyScope
    {
        return PredictionPolicyScope::APP;
    }

    /**
     * Returns true when the related season exists and is active.
     *
     * The nullsafe operator is used because a game may theoretically exist without
     * a season relationship loaded; in that case the policy fails closed (returns false)
     * to prevent predictions on orphaned games.
     *
     * @param  PredictionPolicyContext  $context  The submission context including the player, group, game, and prediction data.
     * @return bool True if the game's season is present and active; false triggers a violation.
     */
    public function passes(PredictionPolicyContext $context): bool
    {
        return (bool) $context->game->season?->active;
    }
}
