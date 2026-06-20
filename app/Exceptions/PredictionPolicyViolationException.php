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
     * @param  PredictionPolicyEvaluationResult  $result  Full evaluation result with violations.
     */
    public function __construct(private readonly PredictionPolicyEvaluationResult $result)
    {
        // Keep RuntimeException message in sync with policy evaluation output.
        parent::__construct($result->summary());
    }

    /**
     * Returns the underlying policy evaluation result.
     */
    public function result(): PredictionPolicyEvaluationResult
    {
        return $this->result;
    }
}