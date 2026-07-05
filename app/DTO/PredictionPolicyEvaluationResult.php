<?php

namespace App\DTO;

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
     * @param  array<int, PredictionPolicyViolation>  $violations  Zero or more policy violations recorded during evaluation.
     */
    public function __construct(
        public array $violations = [],
    ) {}

    /**
     * Returns true when no policy violations were recorded.
     *
     * @return bool True if the submission passed all policies; false if any violation exists.
     */
    public function isValid(): bool
    {
        return empty($this->violations);
    }

    /**
     * Returns true when at least one policy violation exists.
     *
     * @return bool True if one or more violations were recorded; false if the result is valid.
     */
    public function hasViolations(): bool
    {
        return ! $this->isValid();
    }

    /**
     * Returns a human-readable message summarizing the evaluation outcome.
     *
     * @return string A plain-language summary suitable for display; individual violations
     *                are joined with " | " so the full context fits in a single UI message.
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
