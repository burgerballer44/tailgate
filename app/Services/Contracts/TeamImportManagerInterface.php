<?php

namespace App\Services\Contracts;

use App\DTO\ImportResult;
use App\DTO\TeamImportData;

interface TeamImportManagerInterface
{
    /**
     * Get the available sources for team import.
     *
     * @return array<int, array<string, string>>
     */
    public function availableSources(): array;

    /**
     * Import teams based on the provided import data.
     *
     * @param  TeamImportData  $data  The data for the team import.
     * @return ImportResult The result of the team import.
     */
    public function import(TeamImportData $data): ImportResult;
}
