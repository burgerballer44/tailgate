<?php

namespace App\DTO;

use App\Models\Sport;

readonly class ValidatedFollowData
{
    public function __construct(
        public int $team_id,
        public ?Sport $sport,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            team_id: (int) $data['team_id'],
            sport: isset($data['sport']) && $data['sport'] !== null
                ? Sport::from((string) $data['sport'])
                : null,
        );
    }
}
