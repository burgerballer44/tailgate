<?php

namespace App\Services\Contracts;

use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;

/**
 * Provides normalized team import records from an external source.
 *
 * Implementations hide source-specific payload shapes and expose a consistent
 * stream of ImportedTeamData DTOs.
 */
interface TeamImportSourceInterface
{
    /**
     * Identifies the source with a stable key used in import requests.
     *
     * @return string The source key.
     */
    public function key(): string;

    /**
     * Returns a human-readable source name.
     *
     * @return string The source label.
     */
    public function label(): string;

    /**
     * Declares the transport/category of the source (for example API or file-based).
     *
     * @return string The source type.
     */
    public function type(): string;

    /**
     * Returns a short description of source behavior and constraints.
     *
     * @return string The source description.
     */
    public function description(): string;

    /**
     * Streams normalized team records for the requested import options.
     *
     * @param  TeamImportData  $data  Import source selection and runtime options.
     * @return ImportFetchStream<ImportedTeamData> A stream of normalized team DTOs and fetch-time errors.
     */
    public function fetch(TeamImportData $data): ImportFetchStream;
}
