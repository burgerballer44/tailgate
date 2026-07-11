<?php

namespace App\DTO;

/**
 * Represents normalized season-scoped prediction scoring policy update input.
 */
readonly class ValidatedGroupPredictionScoringPolicyData
{
    public function __construct(
        public int $season_id,
        public string $prediction_scoring_policy,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            season_id: (int) $data['season_id'],
            prediction_scoring_policy: (string) $data['prediction_scoring_policy'],
        );
    }
}
