<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\Prediction;
use App\Models\Enums\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Enforces one prediction per game within a group.
 *
 * This group-level rule is optional and runs only when enabled.
 */
class UniqueGroupPredictionPolicy implements PredictionPolicyRuleInterface
{
    /**
     * Returns the stable policy key used for configuration and display.
     *
     * @return string Unique machine-readable key used for policy configuration and violation records.
     */
    public function key(): string
    {
        return 'group-unique-prediction';
    }

    /**
     * Returns the short label used in violation summaries.
     *
     * @return string Human-readable name displayed in violation messages and policy management screens.
     */
    public function label(): string
    {
        return 'Unique group prediction';
    }

    /**
     * Explains the business rule enforced by this policy.
     *
     * @return string Human-readable rule description.
     */
    public function description(): string
    {
        return 'When enabled for a group, only one prediction for a game may exist within that group.';
    }

    /**
     * Identifies this rule as a group-level policy.
     *
     * @return PredictionPolicyScope Indicates whether this policy is enforced globally or only when enabled per group.
     */
    public function scope(): PredictionPolicyScope
    {
        return PredictionPolicyScope::GROUP;
    }

    /**
     * Return true when no other group player has already predicted this game.
     *
     * When evaluating an update, the current prediction is excluded from the duplicate
     * check so a player can re-submit their own prediction without triggering a violation.
     *
     * @param  PredictionPolicyContext  $context  The submission context including the player, group, game, and prediction data.
     * @return bool True if no conflicting prediction exists in the group; false triggers a violation.
     */
    public function passes(PredictionPolicyContext $context): bool
    {
        $duplicateExists = Prediction::query()
            ->where('game_id', $context->game->id)
            ->whereHas('player.member', fn ($query) => $query->where('group_id', $context->group->id))
            ->when($context->prediction?->exists, fn ($query) => $query->whereKeyNot($context->prediction->getKey()))
            ->exists();

        return ! $duplicateExists;
    }
}
