<?php

namespace App\DTO;

use App\Models\Common\DateTimeOrString;
use App\Models\SeasonType;
use App\Models\Sport;

readonly class ValidatedSeasonData
{
    public function __construct(
        public string $name,
        public Sport $sport,
        public SeasonType $season_type,
        public DateTimeOrString $season_start,
        public DateTimeOrString $season_end,
        public ?bool $active,
        public ?DateTimeOrString $active_date,
        public ?DateTimeOrString $inactive_date,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            sport: $data['sport'] instanceof Sport ? $data['sport'] : Sport::from($data['sport']),
            season_type: $data['season_type'] instanceof SeasonType ? $data['season_type'] : SeasonType::from($data['season_type']),
            season_start: $data['season_start'] instanceof DateTimeOrString ? $data['season_start'] : DateTimeOrString::fromString($data['season_start']),
            season_end: $data['season_end'] instanceof DateTimeOrString ? $data['season_end'] : DateTimeOrString::fromString($data['season_end']),
            active: isset($data['active']) ? (bool) $data['active'] : null,
            active_date: isset($data['active_date']) ? ($data['active_date'] instanceof DateTimeOrString ? $data['active_date'] : DateTimeOrString::fromString($data['active_date'])) : null,
            inactive_date: isset($data['inactive_date']) ? ($data['inactive_date'] instanceof DateTimeOrString ? $data['inactive_date'] : DateTimeOrString::fromString($data['inactive_date'])) : null,
        );
    }
}
