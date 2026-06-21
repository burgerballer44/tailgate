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
     * @return array<int, array<string, string>> Source metadata for import selection UIs.
     */
    public function availableSources(): array;

    /**
     * Import games into a season.
     *
     * @param Season $season The season receiving the imported schedule.
     * @param GameImportData $data The import source selection and runtime options.
     * @return ImportResult A summary of imported, updated, and skipped games.
     */
    public function import(Season $season, GameImportData $data): ImportResult;
}
