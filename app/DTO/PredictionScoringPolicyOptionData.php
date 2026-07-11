<?php

namespace App\DTO;

/**
 * Represents one prediction scoring policy option for admin selection UX.
 */
readonly class PredictionScoringPolicyOptionData
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public bool $is_default,
    ) {}
}
