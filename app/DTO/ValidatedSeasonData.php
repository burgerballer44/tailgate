<?php

namespace App\DTO;

use App\Models\SeasonType;
use App\Models\Sport;

readonly class ValidatedSeasonData
{
    public function __construct(
        public string $name,
        public Sport $sport,
        public SeasonType $season_type,
        public ?bool $active,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            sport: $data['sport'] instanceof Sport ? $data['sport'] : Sport::from($data['sport']),
            season_type: $data['season_type'] instanceof SeasonType ? $data['season_type'] : SeasonType::from($data['season_type']),
            active: isset($data['active']) ? (bool) $data['active'] : null,
        );
    }
}
