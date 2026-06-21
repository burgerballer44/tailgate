<?php

namespace App\Services\Contracts;

use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;

/**
 * Provides a team data feed from an external source (e.g., API, CSV).
 * Implementations fetch and stream imported team data in a consistent format, abstracting away
 * source-specific details and allowing multiple team sources to be swapped at runtime.
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
     * Provides the display label used in source selection interfaces.
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
     * Explains source behavior for operator-facing import selection screens.
     *
     * @return string The source description.
     */
    public function description(): string;

    /**
     * Streams normalized team records for the requested import options.
     *
     * @param TeamImportData $data Import source selection and runtime options.
     * @return ImportFetchStream<ImportedTeamData> A stream of normalized team DTOs and fetch-time errors.
     */
    public function fetch(TeamImportData $data): ImportFetchStream;
}
