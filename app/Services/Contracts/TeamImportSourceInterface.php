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
     */
    public function key(): string;

    /**
     * Provides the display label used in source selection interfaces.
     */
    public function label(): string;

    /**
     * Declares the transport/category of the source (for example API or file-based).
     */
    public function type(): string;

    /**
     * Explains source behavior for operator-facing import selection screens.
     */
    public function description(): string;

    /**
     * Streams normalized team records for the requested import options.
     *
     * @return ImportFetchStream<ImportedTeamData>
     */
    public function fetch(TeamImportData $data): ImportFetchStream;
}
