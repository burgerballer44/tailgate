<?php

namespace App\Services;

use App\DTO\PredictionScoringPolicyOptionData;
use App\ScoringPolicies\Contracts\PredictionScoringPolicyOptionInterface;
use App\ScoringPolicies\PlacementPointsPolicy;
use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
use App\Services\Contracts\PredictionScoringPolicyCatalogInterface;

/**
 * Resolves available prediction scoring policies and their UI metadata.
 */
class PredictionScoringPolicyCatalogService implements PredictionScoringPolicyCatalogInterface
{
    /**
     * @var array<int, class-string<PredictionScoringPolicyOptionInterface>>
     */
    private const POLICY_CLASSES = [
        PredictionDifferenceFromScorePointsPolicy::class,
        PlacementPointsPolicy::class,
    ];

    /**
     * @return array<int, PredictionScoringPolicyOptionData>
     */
    public function options(): array
    {
        $defaultKey = $this->defaultKey();

        return array_map(
            static fn (string $policyClass): PredictionScoringPolicyOptionData => new PredictionScoringPolicyOptionData(
                key: $policyClass::key(),
                label: $policyClass::label(),
                description: $policyClass::description(),
                is_default: $policyClass::key() === $defaultKey,
            ),
            self::POLICY_CLASSES,
        );
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_map(
            static fn (string $policyClass): string => $policyClass::key(),
            self::POLICY_CLASSES,
        );
    }

    /**
     * Get the default scoring policy key for new groups.
     */
    public function defaultKey(): string
    {
        return PredictionDifferenceFromScorePointsPolicy::key();
    }

    /**
     * Determine whether the provided key exists in the policy catalog.
     */
    public function isValid(string $key): bool
    {
        return in_array($key, $this->keys(), true);
    }
}
