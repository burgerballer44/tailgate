<?php

namespace App\Services\Contracts;

use App\DTO\ImportResult;
use App\DTO\TeamImportData;

/**
 * Coordinates importing teams from a selected source.
 *
 * Implementations resolve the source, run persistence, and return a summary of
 * imported, updated, and skipped records.
 */
interface TeamImportManagerInterface
{
    /**
     * Returns available team import sources and their metadata.
     *
     * @return array<int, array<string, string>> Source metadata for import configuration.
     */
    public function availableSources(): array;

    /**
     * Import teams based on the provided import data.
     *
     * @param  TeamImportData  $data  The import source selection and runtime options.
     * @return ImportResult A summary of imported, updated, and skipped teams.
     */
    public function import(TeamImportData $data): ImportResult;
}
