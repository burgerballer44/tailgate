<?php

namespace App\DTO;

/**
 * Holds the result of an import operation, including the import source, a human-readable
 * label, the count of successfully imported and updated records, and any errors encountered.
 *
 * @param string $source The unique key for the import source (e.g. 'cfbd', 'csv').
 * @param string $sourceLabel The human-readable label for the import source (e.g. 'CFBD').
 * @param int $importedCount The number of new records successfully created during the import.
 * @param int $updatedCount The number of existing records updated during the import.
 * @param array<int, string> $errors Any errors that occurred during the import process.
 */
readonly class ImportResult
{
    public function __construct(
        public string $source,
        public string $sourceLabel,
        public int $importedCount,
        public int $updatedCount = 0,
        public array $errors = [],
    ) {}

    /**
     * Returns true when at least one new record was successfully imported.
     */
    public function hasImports(): bool
    {
        return $this->importedCount > 0;
    }

    /**
     * Returns true when at least one existing record was updated during the import.
     */
    public function hasUpdates(): bool
    {
        return $this->updatedCount > 0;
    }

    /**
     * Returns true when one or more errors occurred during the import.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Returns true when some records were imported but errors also occurred —
     * i.e. a partial success.
     */
    public function isPartial(): bool
    {
        return $this->hasImports() && $this->hasErrors();
    }
}
