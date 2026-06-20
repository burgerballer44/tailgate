<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Prevents predictions from being submitted after the game lock time.
 * This is an app-level policy that applies to every submission.
 */
class PredictionLockTimePolicy implements PredictionPolicyRuleInterface
{
    /**
     * Returns the stable policy key used for configuration and display.
     */
    public function key(): string
    {
        return 'prediction-lock-time';
    }

    /**
     * Returns the short label used in UI and violation summaries.
     */
    public function label(): string
    {
        return 'Prediction lock time';
    }

    /**
     * Explains the business rule enforced by this policy.
     */
    public function description(): string
    {
        return 'Predictions cannot be submitted or updated after the scheduled game start time.';
    }

    /**
     * Identifies this rule as an app-level policy.
     */
    public function scope(): PredictionPolicyScope
    {
        return PredictionPolicyScope::APP;
    }

    /**
     * Returns true when the game has not yet reached its lock time.
     * TBD games compare by date only so day-based locking still works.
     */
    public function passes(PredictionPolicyContext $context): bool
    {
        $gameDateTime = date_create_immutable((string) $context->game->start_date_time);

        if (! $gameDateTime instanceof \DateTimeImmutable) {
            return true;
        }

        if ($context->game->start_time_tbd) {
            $today = (new \DateTimeImmutable('now'))->format('Y-m-d');
            $gameStart = $gameDateTime->format('Y-m-d');

            return $gameStart >= $today;
        }

        return $gameDateTime >= new \DateTimeImmutable('now');
    }
}