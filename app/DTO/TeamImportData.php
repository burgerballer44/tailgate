<?php

namespace App\DTO;

readonly class TeamImportData
{
    /**
     * Captures source selection and runtime options for a team import request.
     * The options array carries source-specific parameters used during fetch and normalization.
     *
     * @param string $source The unique key for the team import source (e.g. 'cfbd', 'csv', etc.).
     * @param array<string, mixed> $options Additional options for the team import, specific to the source.
     */
    public function __construct(
        public string $source,
        public array $options,
    ) {}
}
