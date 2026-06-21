<?php

namespace App\Services\Contracts;

use App\DTO\ImportResult;
use App\DTO\TeamImportData;

/**
 * Orchestrates the end-to-end process of importing teams from various external sources into the system.
 * Manages source discovery, delegates to the appropriate source for data fetching, handles validation
 * and conflict resolution, and provides comprehensive import result reporting with error tracking.
 */
interface TeamImportManagerInterface
{
    /**
     * Lists import sources and metadata used by team import selection workflows.
     *
     * @return array<int, array<string, string>> Source metadata for import selection UIs.
     */
    public function availableSources(): array;

    /**
     * Import teams based on the provided import data.
     *
     * @param TeamImportData $data The import source selection and runtime options.
     * @return ImportResult A summary of imported, updated, and skipped teams.
     */
    public function import(TeamImportData $data): ImportResult;
}
