<?php

namespace App\Services;

use App\DTO\ImportResult;
use App\DTO\ImportedTeamData;
use App\DTO\TeamImportData;
use App\DTO\ValidatedTeamData;
use App\Exceptions\TeamImportException;
use App\Models\Team;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamImportManagerInterface;
use App\Services\Contracts\TeamImportSourceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamImportManager implements TeamImportManagerInterface
{
    private const DEFAULT_IMPORT_CHUNK_SIZE = 500;

    /**
     * TeamImportManager constructor.
     *
     * @param TeamCommandInterface $teamCommandService The service responsible for team commands (create/update).
     * @param iterable $sources An iterable of available team import sources implementing TeamImportSourceInterface.
     */
    public function __construct(
        private readonly TeamCommandInterface $teamCommandService,
        private readonly iterable $sources,
    ) {}

    /**
     * Get the available sources for team import.
     *
     * @return array<int, array<string, string>> An array of available team import sources with their details.
     */
    public function availableSources(): array
    {
        return collect($this->sources)
            ->map(fn (TeamImportSourceInterface $source): array => [
                'value' => $source->key(),
                'label' => $source->label(),
                'description' => $source->description(),
                'type' => $source->type(),
            ])
            ->values()
            ->all();
    }

    public function import(TeamImportData $data): ImportResult
    {
        $source = $this->resolveSource($data->source);
        $fetchStream = $source->fetch($data);
        $importErrors = [];
        $importedCount = 0;
        $processedCount = 0;
        $chunkSize = $this->resolveChunkSize($data);
        $teamLookupCache = [];

        // Accumulate teams from the stream into fixed-size chunks and process each chunk
        // as it fills up. This keeps at most $chunkSize ImportedTeamData DTOs in memory at once
        // rather than buffering the entire result set.
        $chunk = [];

        foreach ($fetchStream->items() as $team) {
            $chunk[] = $team;

            if (count($chunk) === $chunkSize) {
                $importedCount += $this->importChunk($chunk, $processedCount, $importErrors, $teamLookupCache);
                $processedCount += $chunkSize;
                $chunk = [];
            }
        }

        // process any remaining teams in the final chunk
        if ($chunk !== []) {
            $importedCount += $this->importChunk($chunk, $processedCount, $importErrors, $teamLookupCache);
        }

        $errors = array_merge($fetchStream->errors(), $importErrors);

        return new ImportResult(
            source: $source->key(),
            sourceLabel: $source->label(),
            importedCount: $importedCount,
            errors: $errors,
        );
    }

    /**
     * Persists a single chunk of ImportedTeamData DTOs, updating existing teams and creating new ones.
     * Uses a batched DB lookup to preload all matching teams for the chunk, avoiding N+1 queries.
     *
     * @param  array<int, ImportedTeamData>  $chunk
     * @param  int  $processedCount  Number of teams already processed before this chunk (used for error numbering).
     * @param  array<int, string>  $errors  Accumulated errors array, passed by reference.
     * @param  array<string, array<string, array<string, Team>>>  $teamLookupCache
     * @return int  Number of teams successfully imported in this chunk.
     */
    private function importChunk(array $chunk, int $processedCount, array &$errors, array &$teamLookupCache): int
    {
        $this->warmTeamLookupCache($chunk, $teamLookupCache);
        $importedCount = 0;

        foreach ($chunk as $offset => $team) {
            $teamNumber = $processedCount + $offset + 1;
            $sportKey = Str::lower(trim($team->sport));
            $conferenceKey = Str::lower(trim($team->conference));
            $organizationKey = Str::lower(trim($team->organization));

            $teamData = [
                'organization' => $team->organization,
                'designation' => $team->designation ?? $team->organization,
                'conference' => $team->conference,
                'abbreviation' => $team->abbreviation,
                'color' => $team->color,
                'alternate_color' => $team->alternateColor,
                'logos' => $team->logos,
                'social_media' => $team->socialMedia,
                'sports' => [$team->sport],
                'type' => $team->type,
            ];

            $dto = ValidatedTeamData::fromArray($teamData);
            $existingTeam = $teamLookupCache[$sportKey][$conferenceKey][$organizationKey] ?? null;

            try {
                if ($existingTeam !== null) {
                    $persistedTeam = $this->teamCommandService->update($existingTeam, $dto);
                } else {
                    $persistedTeam = $this->teamCommandService->create($dto);
                }
            } catch (\Throwable $exception) {
                $errors[] = "Skipped team {$teamNumber}: failed to persist imported data.";

                continue;
            }

            // Keep chunk-local duplicates from causing duplicate creates.
            $teamLookupCache[$sportKey][$conferenceKey][$organizationKey] = $persistedTeam;
            $importedCount++;
        }

        return $importedCount;
    }
    
    /**
     * Get the team import source instance matching the given key, or throw an exception if the key is invalid.
     * 
     * @param  string  $key The unique key identifying the desired team import source.
     * @return TeamImportSourceInterface The team import source instance matching the given key.
     */
    private function resolveSource(string $key): TeamImportSourceInterface
    {
        foreach ($this->sources as $source) {
            if ($source->key() === $key) {
                return $source;
            }
        }

        throw new TeamImportException('Selected import source is not available.');
    }

    /**
     * Determines the chunk size to use for the import operation based on the provided TeamImportData options.
     * Falls back to a default chunk size if the provided value is invalid or not set.
     *
     * @param  TeamImportData  $data The data transfer object containing import options, including an optional 'chunk_size'.
     * @return int The resolved chunk size to use for processing the import in batches.
     */
    private function resolveChunkSize(TeamImportData $data): int
    {
        $chunkSize = $data->options['chunk_size'] ?? self::DEFAULT_IMPORT_CHUNK_SIZE;

        return is_int($chunkSize) && $chunkSize > 0
            ? $chunkSize
            : self::DEFAULT_IMPORT_CHUNK_SIZE;
    }

    /**
     * Preloads teams matching the sport/conference combinations in the given
     * chunk into the lookup cache, minimizing database queries during the chunk import.
     * 
     * @param  array<int, ImportedTeamData>  $teams
     * @param  array<string, array<string, array<string, Team>>>  $teamLookupCache
     */
    private function warmTeamLookupCache(array $teams, array &$teamLookupCache): void
    {
        $missingBySport = [];

        foreach ($teams as $team) {

            // lowercase and trim sport and conference to improve cache hit rates
            $sport = Str::lower(trim($team->sport));
            $conference = Str::lower(trim($team->conference));

            // initialize cache buckets if they don't exist yet
            if (! isset($teamLookupCache[$sport])) {
                $teamLookupCache[$sport] = [];
            }

            // track any sport/conference combinations that are missing from the cache so we can batch query them
            if (! isset($teamLookupCache[$sport][$conference])) {
                $teamLookupCache[$sport][$conference] = [];
                $missingBySport[$sport]['sport_value'] = $team->sport;
                $missingBySport[$sport][$conference] = true;
            }
        }

        foreach ($missingBySport as $sport => $requested) {
            $sportValue = $requested['sport_value'] ?? null;

            // if we don't have a valid sport value, we won't be able to match any teams for this sport, so skip it
            if (! is_string($sportValue) || trim($sportValue) === '') {
                continue;
            }

            // get the list of requested conferences for this sport (excluding the 'sport_value' key)
            $conferenceValues = array_values(array_filter(
                array_keys($requested),
                static fn (string $value): bool => $value !== 'sport_value',
            ));

            // if we don't have any valid conference values, we won't be able to match any teams for this sport, so skip it
            if ($conferenceValues === []) {
                continue;
            }

            // query for teams matching any of the requested conference values for this sport
            $matches = Team::query()
                ->whereHas('sports', fn ($query) => $query->where('sport', $sportValue))
                ->whereIn(DB::raw('LOWER(conference)'), $conferenceValues)
                ->get();

            // index the matching teams in the cache by their lowercase conference and organization values for quick lookup during import
            foreach ($matches as $matchedTeam) {
                $conference = Str::lower(trim($matchedTeam->conference));
                $organization = Str::lower(trim($matchedTeam->organization));

                if (! isset($teamLookupCache[$sport][$conference][$organization])) {
                    $teamLookupCache[$sport][$conference][$organization] = $matchedTeam;
                }
            }
        }
    }
}