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
        public ?bool $active,
        public ?DateOrString $active_date,
        public ?DateOrString $inactive_date,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            sport: $data['sport'] instanceof Sport ? $data['sport'] : Sport::from($data['sport']),
            season_type: $data['season_type'] instanceof SeasonType ? $data['season_type'] : SeasonType::from($data['season_type']),
            season_start: $data['season_start'] instanceof DateOrString ? $data['season_start'] : DateOrString::fromString($data['season_start']),
            season_end: $data['season_end'] instanceof DateOrString ? $data['season_end'] : DateOrString::fromString($data['season_end']),
            active: isset($data['active']) ? (bool) $data['active'] : null,
            active_date: isset($data['active_date']) ? ($data['active_date'] instanceof DateOrString ? $data['active_date'] : DateOrString::fromString($data['active_date'])) : null,
            inactive_date: isset($data['inactive_date']) ? ($data['inactive_date'] instanceof DateOrString ? $data['inactive_date'] : DateOrString::fromString($data['inactive_date'])) : null,
        );
    }
}