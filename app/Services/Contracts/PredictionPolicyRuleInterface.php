<?php

namespace App\Services\Contracts;

use App\DTO\PredictionPolicyContext;
use App\Models\PredictionPolicyScope;

/**
 * Implementations provide metadata for UI/reporting and policy evaluation logic.
 */
interface PredictionPolicyRuleInterface
{
    /**
     * Returns a stable machine-readable identifier for this rule.
     */
    public function key(): string;

    /**
     * Returns a short human-readable title shown in UI and feedback.
     */
    public function label(): string;

    /**
     * Returns a human-readable explanation of what the rule enforces.
     */
    public function description(): string;

    /**
     * Returns whether this rule is app-level or group-level.
     */
    public function scope(): PredictionPolicyScope;

    /**
     * Evaluates the rule against the provided prediction submission context.
     * Returns true when the submission satisfies this rule, false otherwise.
     */
    public function passes(PredictionPolicyContext $context): bool;
}