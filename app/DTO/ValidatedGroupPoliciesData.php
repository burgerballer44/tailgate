<?php

namespace App\DTO;

/**
 * Represents normalized group policy update input.
 *
 * This DTO is intentionally narrow so policy updates cannot mutate core
 * group metadata such as name, owner, or limits.
 */
readonly class ValidatedGroupPoliciesData
{
    /**
     * @param  array<int, string>  $enabled_prediction_policies  Group-level policy keys to enable.
     */
    public function __construct(
        public array $enabled_prediction_policies,
    ) {}

    /**
     * Construct from raw validated request input.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enabled_prediction_policies: array_values(array_filter((array) ($data['enabled_prediction_policies'] ?? []))),
        );
    }
}
