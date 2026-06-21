<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Requires predictions to be submitted at least 30 minutes before kickoff.
 * This is a group-level policy intended for more competitive groups that want
 * a stricter lead time than the default app lock-time rule.
 */
class MinimumLeadTimeBeforeLockPolicy implements PredictionPolicyRuleInterface
{
    private const MINIMUM_LEAD_TIME_MINUTES = 30;

    /**
     * Returns the stable policy key used for configuration and display.
     *
     * @return string Unique machine-readable key used for policy configuration and violation records.
     */
    public function key(): string
    {
        return 'minimum-lead-time-before-lock';
    }

    /**
     * Returns the short label used in UI and violation summaries.
     *
     * @return string Human-readable name displayed in violation messages and policy management screens.
     */
    public function label(): string
    {
        return 'Minimum lead time before lock';
    }

    /**
     * Explains the business rule enforced by this policy.
     *
     * @return string Full human-readable description of the constraint, suitable for user-facing display.
     */
    public function description(): string
    {
        return 'Predictions must be submitted at least 30 minutes before the scheduled game start time.';
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
     * Returns true when the submission occurs at least 30 minutes before kickoff.
     *
     * If the game's start time cannot be parsed as a valid datetime, the check is
     * skipped and the submission is allowed through — an unparseable time is treated
     * as no time rather than blocking all predictions for that game.
     *
     * @param PredictionPolicyContext $context The submission context including the player, group, game, and prediction data.
     * @return bool True if the submission is within the required lead time; false triggers a violation.
     */
    public function passes(PredictionPolicyContext $context): bool
    {
        $gameDateTime = date_create_immutable((string) $context->game->start_date_time);

        if (! $gameDateTime instanceof \DateTimeImmutable) {
            return true;
        }

        $cutoff = (new \DateTimeImmutable('now'))->modify('+' . self::MINIMUM_LEAD_TIME_MINUTES . ' minutes');

        return $gameDateTime >= $cutoff;
    }
}