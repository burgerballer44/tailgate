<?php

namespace App\DTO;

use App\Models\SeasonType;
use App\Models\Sport;

/**
 * Represents normalized season input used by season management workflows.
 * Encodes season identity, sport, type, and activation state in a persistence-ready shape.
 *
 * @param  string  $name  The name of the season (e.g., "2024", "Fall 2024").
 * @param  Sport  $sport  The sport enum associated with the season (e.g., Football, Basketball).
 * @param  SeasonType  $season_type  The type of season enum (e.g., Regular, Playoff, Preseason).
 * @param  bool|null  $active  Whether the season is currently active, or null to use system default.
 */
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
