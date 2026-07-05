<?php

namespace App\DTO;

/**
 * Summarizes the outcome of an import run for reporting and UI feedback.
 * Tracks source identity, created/updated counts, and accumulated errors.
 */
readonly class ImportResult
{
    /**
     * @param  string  $source  The unique key for the import source (e.g. 'cfbd', 'csv').
     * @param  string  $sourceLabel  The human-readable label for the import source (e.g. 'CFBD').
     * @param  int  $importedCount  The number of new records successfully created during the import.
     * @param  int  $updatedCount  The number of existing records updated during the import.
     * @param  array<int, string>  $errors  Any errors that occurred during the import process.
     */
    public function __construct(
        public string $source,
        public string $sourceLabel,
        public int $importedCount,
        public int $updatedCount = 0,
        public array $errors = [],
    ) {}

    /**
     * Indicates whether the run created at least one new record.
     *
     * @return bool True if at least one record was created; false otherwise.
     */
    public function hasImports(): bool
    {
        return $this->importedCount > 0;
    }

    /**
     * Indicates whether the run updated at least one existing record.
     *
     * @return bool True if at least one record was updated; false otherwise.
     */
    public function hasUpdates(): bool
    {
        return $this->updatedCount > 0;
    }

    /**
     * Indicates whether one or more import errors were recorded.
     *
     * @return bool True if the errors array is non-empty; false otherwise.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Indicates whether the run partially succeeded (imports occurred alongside errors).
     *
     * Useful for distinguishing a full success from a run where some records were
     * persisted but others failed, so callers can surface appropriate feedback.
     *
     * @return bool True if at least one record was imported and at least one error was recorded.
     */
    public function isPartial(): bool
    {
        return $this->hasImports() && $this->hasErrors();
    }
}
