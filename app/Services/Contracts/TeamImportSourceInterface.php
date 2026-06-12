<?php

namespace App\Services\Contracts;

use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;

interface TeamImportSourceInterface
{
    /**
     * Get the unique key for this team import source.
     */
    public function key(): string;

    /**
     * Get the human-readable label for this team import source.
     */
    public function label(): string;

    /**
     * Get the type of this team import source (e.g. 'api', 'csv', etc.).
     */
    public function type(): string;

    /**
     * Get the description of this team import source.
     */
    public function description(): string;

    /**
     * Fetch teams from this source based on the provided import data.
     *
     * @return ImportFetchStream<ImportedTeamData>
     */
    public function fetch(TeamImportData $data): ImportFetchStream;
}
