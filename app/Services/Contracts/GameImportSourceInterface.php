<?php

namespace App\Services\Contracts;

use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Models\Season;

/**
 * Provides a game data feed from an external source (e.g., API, CSV).
 * Implementations fetch and stream imported game data in a consistent format, abstracting away
 * source-specific details and allowing multiple game sources to be swapped at runtime.
 */
interface GameImportSourceInterface
{
    /**
     * Identifies the source with a stable key used in import requests.
     */
    public function key(): string;

    /**
     * Provides the display label used in source selection interfaces.
     */
    public function label(): string;

    /**
     * Declares the transport/category of the source (for example API or file-based).
     */
    public function type(): string;

    /**
     * Explains source behavior for operator-facing import selection screens.
     */
    public function description(): string;

    /**
     * Streams normalized game records for the requested season and import options.
     *
     * @param  Season  $season  The season to fetch games for.
     * @param  GameImportData  $data  The data for the game import.
     * @return ImportFetchStream<ImportedGameData>  The stream of fetched games.
     */
    public function fetch(Season $season, GameImportData $data): ImportFetchStream;
}
