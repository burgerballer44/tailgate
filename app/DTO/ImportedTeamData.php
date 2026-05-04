<?php

namespace App\DTO;

readonly class ImportedTeamData
{
    /**
     * Holds the necessary information about a team that has been imported from an external source,
    * including the team's organization name, designation, conference affiliation, abbreviation, colors, logos and social media links.
     * 
     * @param string $organization The full name of the team's organization (e.g. "University of Alabama").
     * @param string $sport The sport the team participates in (e.g. "football", "basketball", etc.).
     * @param string $conference The conference the team belongs to (e.g. "SEC").
     * @param string $type The type of the team (e.g. "college", "professional").
     * @param string|null $designation The team's designation or nickname (e.g. "Alabama Crimson Tide").
     * @param string|null $abbreviation The team's abbreviation or short name (e.g. "ALA").
     * @param string|null $color The team's primary color in hex format (e.g. "#9E1B32").
     * @param array|null $logos An array of logo URLs for the team, keyed by size or type (e.g. ['small' => '...', 'large' => '...']).
     * @param array|null $socialMedia An array of social media links for the team, keyed by platform (e.g. [['label' => 'twitter', 'url' => '...'], ['label' => 'facebook', 'url' => '...']]).
     */
    public function __construct(
        public string $organization,
        public string $sport,
        public string $conference,
        public string $type,
        public ?string $designation,
        public ?string $abbreviation,
        public ?string $color,
        public ?array $logos,
        public ?array $socialMedia
    ) {}
}