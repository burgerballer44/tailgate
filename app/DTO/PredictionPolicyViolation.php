<?php

namespace App\DTO;

use App\Models\PredictionPolicyScope;

/**
 * Represents a single failed prediction policy check.
 *
 * This DTO carries both machine-friendly and human-friendly metadata so callers
 * can store, inspect, and present violations consistently.
 */
readonly class PredictionPolicyViolation
{
    /**
     * @param  string  $key  Stable policy identifier used for matching and analytics.
     * @param  string  $label  Human-readable policy name.
     * @param  string  $description  Human-readable reason the policy failed.
     * @param  PredictionPolicyScope  $scope  Policy scope (app-level or group-level).
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public PredictionPolicyScope $scope,
    ) {}

    /**
     * Returns a compact, user-facing summary line for this violation.
     */
    public function toSummary(): string
    {
        return $this->label.': '.$this->description;
    }
}