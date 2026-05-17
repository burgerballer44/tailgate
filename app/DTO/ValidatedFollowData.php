<?php

namespace App\DTO;

readonly class ValidatedFollowData
{
    public function __construct(
        public int $team_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            team_id: (int) $data['team_id'],
        );
    }
}
