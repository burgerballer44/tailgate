<?php

namespace App\DTO;

use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;

/**
 * Represents normalized team input ready for create/update operations.
 * Captures canonical team identity, classification, visual metadata, and sport associations
 * including per-sport conference assignments.
 */
readonly class ValidatedTeamData
{
    /**
     * @param  string  $organization  The full name of the team's organization (e.g. "University of Alabama").
     * @param  string  $designation  The team's designation or nickname (e.g. "Alabama Crimson Tide").
     * @param  string  $conference  A default conference value used when sport-specific conferences are not provided.
     * @param  string|null  $abbreviation  The team's abbreviation or short code (e.g. "ALA"), or null if not provided.
     * @param  string|null  $color  The team's primary color in hex format (e.g. "#9E1B32"), or null if not provided.
     * @param  array|null  $logos  Logo URLs keyed by type or size, or null if not provided.
     * @param  array|null  $socialMedia  Social media links keyed by platform, or null if not provided.
     * @param  TeamType  $type  The team type enum (e.g. College, Professional).
     * @param  array  $sports  An array of Sport enum instances the team participates in.
     * @param  array<string, string>  $sportConferences  A map of sport value => conference.
     */
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
        public array $sportConferences,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * Accepts both raw string values and already-cast Sport enum instances, which allows
     * the factory to be used in both HTTP and programmatic contexts. A top-level conference value
     * is treated as the default for each selected sport unless explicit sport_conferences are supplied.
     *
     * @param  array<string, mixed>  $data  Raw input data containing team identity, classification, and optional metadata.
     */
    public static function fromArray(array $data): self
    {
        $sports = [];
        if (isset($data['sports']) && is_array($data['sports'])) {
            $sports = array_map(function ($sport) {
                return $sport instanceof Sport ? $sport : Sport::from($sport);
            }, $data['sports']);
        }

        $defaultConference = trim((string) ($data['conference'] ?? Team::UNKNOWN_CONFERENCE));
        if ($defaultConference === '') {
            $defaultConference = Team::UNKNOWN_CONFERENCE;
        }

        $providedSportConferences = [];
        if (isset($data['sport_conferences']) && is_array($data['sport_conferences'])) {
            foreach ($data['sport_conferences'] as $sportValue => $conference) {
                $sportKey = trim((string) $sportValue);
                $conferenceValue = trim((string) $conference);

                if ($sportKey === '' || $conferenceValue === '') {
                    continue;
                }

                try {
                    $normalizedSport = Sport::from($sportKey)->value;
                } catch (\ValueError) {
                    continue;
                }

                $providedSportConferences[$normalizedSport] = $conferenceValue;
            }
        }

        $sportConferences = [];
        foreach ($sports as $sport) {
            $sportConferences[$sport->value] = $providedSportConferences[$sport->value] ?? $defaultConference;
        }

        return new self(
            organization: (string) $data['organization'],
            designation: (string) $data['designation'],
            conference: $defaultConference,
            abbreviation: $data['abbreviation'] ?? null,
            color: $data['color'] ?? null,
            logos: isset($data['logos']) && is_array($data['logos']) ? array_values($data['logos']) : null,
            socialMedia: isset($data['social_media']) && is_array($data['social_media']) ? array_values($data['social_media']) : null,
            type: TeamType::from($data['type']),
            sports: $sports,
            sportConferences: $sportConferences,
        );
    }
}
