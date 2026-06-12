<?php

namespace App\Services\Contracts;

use App\DTO\GameImportData;
use App\DTO\ImportResult;
use App\Models\Season;

/**
 * Orchestrates the end-to-end process of importing games from various external sources into a season.
 * Manages source discovery, delegates to the appropriate source for data fetching, handles validation
 * and conflict resolution, and provides comprehensive import result reporting with error tracking.
 */
interface GameImportManagerInterface
{
    /**
     * Lists import sources and metadata used by game import selection workflows.
     *
     * @return array<int, array<string, string>>
     */
    public function availableSources(): array;

    /**
     * Import games into a season.
     *
     * @param  Season  $season  The season to import games into.
     * @param  GameImportData  $data  The data for the game import.
     * @return ImportResult The result of the game import.
     */
    public function import(Season $season, GameImportData $data): ImportResult;
}
