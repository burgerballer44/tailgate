<?php

namespace App\DTO;

use App\Models\SeasonType;
use App\Models\Sport;
use App\Models\Common\DateOrString;

readonly class ValidatedSeasonData
{
    public function __construct(
        public string $name,
        public Sport $sport,
        public SeasonType $season_type,
        public DateOrString $season_start,
        public DateOrString $season_end,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            sport: $data['sport'] instanceof Sport ? $data['sport'] : Sport::from($data['sport']),
            season_type: $data['season_type'] instanceof SeasonType ? $data['season_type'] : SeasonType::from($data['season_type']),
            season_start: $data['season_start'] instanceof DateOrString ? $data['season_start'] : DateOrString::fromString($data['season_start']),
            season_end: $data['season_end'] instanceof DateOrString ? $data['season_end'] : DateOrString::fromString($data['season_end']),
        );
    }
}