<?php

namespace App\DTO;

readonly class TeamImportData
{
    /**
     * Holds the necessary information to perform a team import from a given source.
     * The options array can contain any additional parameters needed to fetch and process the team data from the source.
     * 
     * @param string $source The unique key for the team import source (e.g. 'cfbd', 'csv', etc.).
     * @param array<string, mixed> $options Additional options for the team import, specific to the source.
     */
    public function __construct(
        public string $source,
        public array $options,
    ) {}
}