<?php

namespace App\Services\Contracts;

use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Models\Season;

interface GameImportSourceInterface
{
    /**
     * Get the unique key for this game import source.
     */
    public function key(): string;

    /**
     * Get the human-readable label for this game import source.
     */
    public function label(): string;

    /**
     * Get the type of this game import source (e.g. 'cfbd', 'csv', etc.).
     */
    public function type(): string;

    /**
     * Get the description of this game import source.
     */
    public function description(): string;

    /**
     * Fetch games from this source for a given season.
     *
     * @param  Season  $season  The season to fetch games for.
     * @param  GameImportData  $data  The data for the game import.
     * @return ImportFetchStream<ImportedGameData> The stream of fetched games.
     */
    public function fetch(Season $season, GameImportData $data): ImportFetchStream;
}
