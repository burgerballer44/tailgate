<?php

namespace App\Services\Contracts;

use App\DTO\GameImportData;
use App\DTO\ImportResult;
use App\Models\Season;

interface GameImportManagerInterface
{
    /**
     * Get the available sources for game import.
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
