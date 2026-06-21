<?php

namespace App\Exceptions;

use App\DTO\PredictionPolicyEvaluationResult;
use RuntimeException;

/**
 * Raised when prediction policy validation fails.
 *
 * The exception message is derived from the evaluation summary and the full
 * structured result remains accessible for downstream handlers.
 */
class PredictionPolicyViolationException extends RuntimeException
{
    /**
     * @param PredictionPolicyEvaluationResult $result The full evaluation result carrying all recorded violations.
     */
    public function __construct(private readonly PredictionPolicyEvaluationResult $result)
    {
        // Keeps the RuntimeException message in sync with the structured evaluation output
        // so callers that catch the base exception still get a readable description.
        parent::__construct($result->summary());
    }

    /**
     * Returns the underlying policy evaluation result.
     *
     * @return PredictionPolicyEvaluationResult The result object containing all recorded violations.
     */
    public function result(): PredictionPolicyEvaluationResult
    {
        return $this->result;
    }
}