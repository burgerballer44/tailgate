<?php

namespace App\DTO;

use App\Models\Sport;

readonly class ValidatedTeamData
{
    public function __construct(
        public ?string $designation,
        public ?string $mascot,
        public ?Sport $sport,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            designation: isset($data['designation']) ? (string) $data['designation'] : null,
            mascot: isset($data['mascot']) ? (string) $data['mascot'] : null,
            sport: isset($data['sport'])
                ? ($data['sport'] instanceof Sport
                    ? $data['sport']
                    : Sport::tryFrom($data['sport']))
                : null,
        );
    }
}