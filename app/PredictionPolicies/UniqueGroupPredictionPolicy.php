<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\Prediction;
use App\Models\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Prevents multiple players in the same group from submitting predictions for the same game.
 * This is a group-level policy that only runs when enabled by the group.
 */
class UniqueGroupPredictionPolicy implements PredictionPolicyRuleInterface
{
    /**
     * Returns the stable policy key used for configuration and display.
     */
    public function key(): string
    {
        return 'group-unique-prediction';
    }

    /**
     * Returns the short label used in UI and violation summaries.
     */
    public function label(): string
    {
        return 'Unique group prediction';
    }

    /**
     * Explains the business rule enforced by this policy.
     */
    public function description(): string
    {
        return 'When enabled for a group, only one prediction for a game may exist within that group.';
    }

    /**
     * Identifies this rule as a group-level policy.
     */
    public function scope(): PredictionPolicyScope
    {
        return PredictionPolicyScope::GROUP;
    }

    /**
     * Returns true when no other player in the same group has already predicted the game.
     * If evaluating an update, the current prediction is excluded so self-updates remain valid.
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