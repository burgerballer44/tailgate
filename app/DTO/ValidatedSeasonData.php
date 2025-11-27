<?php

namespace App\DTO;

use App\Models\SeasonType;
use App\Models\Sport;

readonly class ValidatedSeasonData
{
    public function __construct(
        public ?string $name,
        public ?Sport $sport,
        public ?SeasonType $season_type,
        public ?string $season_start,
        public ?string $season_end,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            sport: isset($data['sport'])
                ? ($data['sport'] instanceof Sport
                    ? $data['sport']
                    : Sport::tryFrom($data['sport']))
                : null,
            season_type: isset($data['season_type'])
                ? ($data['season_type'] instanceof SeasonType
                    ? $data['season_type']
                    : SeasonType::tryFrom($data['season_type']))
                : null,
            season_start: isset($data['season_start']) ? (string) $data['season_start'] : null,
            season_end: isset($data['season_end']) ? (string) $data['season_end'] : null,
        );
    }
}