<?php

namespace App\DTO;

readonly class GameImportData
{
    /**
     * Captures source selection and runtime options for a game import request.
     * The options array carries source-specific parameters used during fetch and normalization.
     *
     * @param  string  $source  The unique key for the game import source (e.g. 'cfbd', 'csv', etc.).
     * @param  array<string, mixed>  $options  Additional options for the game import, specific to the source.
     */
    public function __construct(
        public string $source,
        public array $options,
    ) {}
}
