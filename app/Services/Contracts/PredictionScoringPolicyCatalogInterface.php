<?php

namespace App\Services\Contracts;

use App\DTO\PredictionScoringPolicyOptionData;

/**
 * Provides scoring policy option metadata for validation and UI rendering.
 */
interface PredictionScoringPolicyCatalogInterface
{
    /**
     * @return array<int, PredictionScoringPolicyOptionData>
     */
    public function options(): array;

    /**
     * @return array<int, string>
     */
    public function keys(): array;

    /**
     * Get the default scoring policy key used for new groups.
     */
    public function defaultKey(): string;

    /**
     * Determine whether the provided scoring policy key is supported.
     */
    public function isValid(string $key): bool;
}
