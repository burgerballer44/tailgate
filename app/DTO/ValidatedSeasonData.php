<?php

namespace App\DTO;

use App\Models\SeasonType;
use App\Models\Sport;

/**
 * Represents normalized season input used by season management workflows.
 * Encodes season identity, sport, type, and activation state in a persistence-ready shape.
 */
readonly class ValidatedSeasonData
{
    /**
     * @param string $name The name of the season (e.g. "2024", "Fall 2024").
     * @param Sport $sport The sport enum associated with the season.
     * @param SeasonType $season_type The type of season (e.g. Regular, Playoff, Preseason).
     * @param bool|null $active Whether the season is currently active, or null to use the system default.
     */
    public function __construct(
        public string $name,
        public Sport $sport,
        public SeasonType $season_type,
        public ?bool $active,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * Accepts both raw string values and already-cast enum instances for sport and season_type,
     * which allows the factory to be used in both HTTP and programmatic contexts.
     *
     * @param array<string, mixed> $data Raw input data containing name, sport, season_type, and optionally active.
     * @return self
     */
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
