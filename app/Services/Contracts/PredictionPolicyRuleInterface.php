<?php

namespace App\Services\Contracts;

use App\DTO\PredictionPolicyContext;
use App\Models\PredictionPolicyScope;

/**
 * Defines a single prediction policy rule.
 *
 * Each rule provides stable metadata for configuration and violation reporting,
 * and encapsulates one pass/fail enforcement check.
 */
interface PredictionPolicyRuleInterface
{
    /**
     * Returns a stable machine-readable identifier for this rule.
     *
     * @return string The policy key used for configuration and violation records.
     */
    public function key(): string;

    /**
     * Returns a short human-readable name for this rule.
     *
     * @return string The display label for the policy.
     */
    public function label(): string;

    /**
     * Returns a human-readable explanation of what the rule enforces.
     *
     * @return string The human-readable policy description.
     */
    public function description(): string;

    /**
     * Returns whether this rule is app-level or group-level.
     *
     * @return PredictionPolicyScope The policy scope.
     */
    public function scope(): PredictionPolicyScope;

    /**
     * Evaluates the rule against the provided prediction submission context.
     * Returns true when the submission satisfies this rule, false otherwise.
     *
     * @param  PredictionPolicyContext  $context  The submission context used by the policy.
     * @return bool True when the submission passes the policy.
     */
    public function passes(PredictionPolicyContext $context): bool;
}
