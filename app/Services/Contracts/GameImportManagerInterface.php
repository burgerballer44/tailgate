<?php

namespace App\Services\Contracts;

use App\DTO\GameImportData;
use App\DTO\ImportResult;
use App\Models\Season;

/**
 * Coordinates importing games from a selected source into a season.
 *
 * Implementations resolve the source, run import persistence, and return a
 * result summary with counts and errors.
 */
interface GameImportManagerInterface
{
    /**
     * Returns available game import sources and their metadata.
     *
     * @return array<int, array<string, string>> Source metadata for import configuration.
     */
    public function availableSources(): array;

    /**
     * Import games into a season.
     *
     * @param  Season  $season  The season receiving the imported schedule.
     * @param  GameImportData  $data  The import source selection and runtime options.
     * @return ImportResult A summary of imported, updated, and skipped games.
     */
    public function import(Season $season, GameImportData $data): ImportResult;
}
