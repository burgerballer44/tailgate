<?php

namespace App\DTO;

use App\DTO\PredictionPolicyViolation;

/**
 * Represents the final outcome of policy evaluation for a prediction submission.
 *
 * This DTO is returned by policy evaluation services and is used by callers to:
 * - check whether submission can proceed,
 * - inspect violated policies,
 * - build user-facing feedback messages.
 */
readonly class PredictionPolicyEvaluationResult
{
    /**
     * @param  array<int, PredictionPolicyViolation>  $violations
     */
    public function __construct(
        public array $violations = [],
    ) {}

    /**
     * Returns true when no policy violations were recorded.
     */
    public function isValid(): bool
    {
        return empty($this->violations);
    }

    /**
     * Returns true when at least one policy violation exists.
     */
    public function hasViolations(): bool
    {
        return !$this->isValid();
    }

    /**
     * Builds a readable message summarizing the evaluation outcome.
     */
    public function summary(): string
    {
        if ($this->isValid()) {
            return 'Prediction submission is valid.';
        }

        // Join each violation summary so controllers can display one complete message.
        return 'Prediction submission violates the following policies: '.implode(' | ', array_map(
            static fn (PredictionPolicyViolation $violation): string => $violation->toSummary(),
            $this->violations
        ));
    }
}