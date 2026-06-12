<?php

namespace App\DTO;

use App\Models\Sport;
use App\Models\TeamType;

/**
 * Represents normalized team input ready for create/update operations.
 * Captures canonical team identity, classification, visual metadata, and sport associations.
 *
 * @param  string  $organization  The full name of the team's organization (e.g., "University of Alabama").
 * @param  string  $designation  The team's designation or nickname (e.g., "Alabama Crimson Tide").
 * @param  string  $conference  The conference the team belongs to (e.g., "SEC").
 * @param  string|null  $abbreviation  The team's abbreviation or short code (e.g., "ALA"), or null if not provided.
 * @param  string|null  $color  The team's primary color in hex format (e.g., "#9E1B32"), or null if not provided.
 * @param  array|null  $logos  An array of logo URLs keyed by type or size, or null if not provided.
 * @param  array|null  $socialMedia  An array of social media links keyed by platform, or null if not provided.
 * @param  TeamType  $type  The team type enum (e.g., College, Professional).
 * @param  array  $sports  An array of Sport enum instances the team participates in.
 */
readonly class ValidatedTeamData
{
    public function __construct(
        public string $organization,
        public string $designation,
        public string $conference,
        public ?string $abbreviation,
        public ?string $color,
        public ?array $logos,
        public ?array $socialMedia,
        public TeamType $type,
        public array $sports,
    ) {}

    public static function fromArray(array $data): self
    {
        // convert sports values to Sport enum instances
        $sports = [];
        if (isset($data['sports']) && is_array($data['sports'])) {
            $sports = array_map(function ($sport) {
                return $sport instanceof Sport ? $sport : Sport::from($sport);
            }, $data['sports']);
        }

        return new self(
            organization: (string) $data['organization'],
            designation: (string) $data['designation'],
            conference: (string) $data['conference'],
            abbreviation: $data['abbreviation'] ?? null,
            color: $data['color'] ?? null,
            logos: isset($data['logos']) && is_array($data['logos']) ? array_values($data['logos']) : null,
            socialMedia: isset($data['social_media']) && is_array($data['social_media']) ? array_values($data['social_media']) : null,
            type: TeamType::from($data['type']),
            sports: $sports,
        );
    }
}
