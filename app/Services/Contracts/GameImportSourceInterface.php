<?php

namespace App\Services\Contracts;

use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Models\Season;

/**
 * Provides normalized game import records from an external source.
 *
 * Implementations hide source-specific payload shapes and expose a consistent
 * stream of ImportedGameData DTOs.
 */
interface GameImportSourceInterface
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
     * Streams normalized game records for the requested season and import options.
     *
     * @param  Season  $season  The season to fetch games for.
     * @param  GameImportData  $data  Import source selection and runtime options.
     * @return ImportFetchStream<ImportedGameData> A stream of normalized game DTOs and fetch-time errors.
     */
    public function fetch(Season $season, GameImportData $data): ImportFetchStream;
}
