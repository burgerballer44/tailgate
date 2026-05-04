<?php

namespace App\DTO;

readonly class GameImportData
{
    /**
     * Holds the necessary information to perform a game import from a given source.
     * The options array can contain any additional parameters needed to fetch and process the game data from the source.
     * 
     * @param string $source The unique key for the game import source (e.g. 'cfbd', 'csv', etc.).
     * @param array<string, mixed> $options Additional options for the game import, specific to the source.
     */
    public function __construct(
        public string $source,
        public array $options,
    ) {}
}