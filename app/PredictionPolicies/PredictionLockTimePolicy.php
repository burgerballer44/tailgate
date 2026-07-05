<?php

namespace App\PredictionPolicies;

use App\DTO\PredictionPolicyContext;
use App\Models\PredictionPolicyScope;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Enforces the default game lock-time rule.
 *
 * This app-level policy applies to every prediction submission and update.
 */
class PredictionLockTimePolicy implements PredictionPolicyRuleInterface
{
    /**
     * Returns the stable policy key used for configuration and display.
     *
     * @return string Unique machine-readable key used for policy configuration and violation records.
     */
    public function key(): string
    {
        return 'prediction-lock-time';
    }

    /**
     * Returns the short label used in violation summaries.
     *
     * @return string Human-readable name displayed in violation messages and policy management screens.
     */
    public function label(): string
    {
        return 'Prediction lock time';
    }

    /**
     * Explains the business rule enforced by this policy.
     *
     * @return string Human-readable rule description.
     */
    public function description(): string
    {
        return 'Predictions cannot be submitted or updated after the scheduled game start time.';
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
     * Return true when the game's lock time has not passed.
     *
     * TBD games compare by date only so day-based locking still works — comparing
     * full datetimes for a TBD game would incorrectly block same-day submissions
     * made before midnight.
     *
     * If start time parsing fails, the rule allows the submission to avoid
     * blocking all predictions because of malformed source data.
     *
     * @param  PredictionPolicyContext  $context  The submission context including the player, group, game, and prediction data.
     * @return bool True if the game's lock time has not yet passed; false triggers a violation.
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
