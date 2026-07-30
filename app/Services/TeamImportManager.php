<?php

namespace App\Services;

use App\DTO\ImportedTeamData;
use App\DTO\ImportResult;
use App\DTO\TeamImportData;
use App\DTO\ValidatedTeamData;
use App\Exceptions\TeamImportException;
use App\Models\Enums\Sport;
use App\Models\Enums\TeamFallback;
use App\Models\Enums\TeamType;
use App\Models\Team;
use App\Services\Contracts\TeamCommandInterface;
use App\Services\Contracts\TeamImportManagerInterface;
use App\Services\Contracts\TeamImportSourceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Coordinates the full team import pipeline from external sources into local persistence.
 * Applies validation, batching, conflict handling, and result reporting so imports remain reliable and observable.
 */
class TeamImportManager implements TeamImportManagerInterface
{
    private const DEFAULT_IMPORT_CHUNK_SIZE = 500;

    /**
     * Create a team import coordinator with the configured command service and source registry.
     *
     * @param  TeamCommandInterface  $teamCommandService  The service responsible for team create and update operations.
     * @param  iterable<int, TeamImportSourceInterface>  $sources  Available team import sources keyed by iteration order.
     */
    public function __construct(
        private readonly TeamCommandInterface $teamCommandService,
        private readonly iterable $sources,
    ) {}

    /**
     * Return team import source metadata.
     *
     * @return array<int, array<string, string>> Available source metadata for import configuration.
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

    /**
     * Import teams from the configured source, batching work to keep memory usage bounded.
     *
     * @param  TeamImportData  $data  Import source selection and runtime options.
     * @return ImportResult A summary of imported, updated, and skipped teams.
     *
     * @throws TeamImportException When the requested source key is unavailable.
     */
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
        $updatedCount = 0;

        foreach ($fetchStream->items() as $team) {
            $chunk[] = $team;

            if (count($chunk) === $chunkSize) {
                [$chunkImported, $chunkUpdated] = $this->importChunk($chunk, $processedCount, $importErrors, $teamLookupCache);
                $importedCount += $chunkImported;
                $updatedCount += $chunkUpdated;
                $processedCount += $chunkSize;
                $chunk = [];
            }
        }

        // process any remaining teams in the final chunk
        if ($chunk !== []) {
            [$chunkImported, $chunkUpdated] = $this->importChunk($chunk, $processedCount, $importErrors, $teamLookupCache);
            $importedCount += $chunkImported;
            $updatedCount += $chunkUpdated;
        }

        $errors = array_merge($fetchStream->errors(), $importErrors);

        return new ImportResult(
            source: $source->key(),
            sourceLabel: $source->label(),
            importedCount: $importedCount,
            updatedCount: $updatedCount,
            errors: $errors,
        );
    }

    /**
     * Persists a single chunk of ImportedTeamData DTOs, updating existing teams and creating new ones.
     * Uses a batched DB lookup to preload all matching teams for the chunk, avoiding N+1 queries.
     *
     * @return array{0: int, 1: int} A tuple of [importedCount, updatedCount] for the chunk.
     */
    private function importChunk(array $chunk, int $processedCount, array &$errors, array &$teamLookupCache): array
    {
        $this->warmTeamLookupCache($chunk, $teamLookupCache);
        $importedCount = 0;
        $updatedCount = 0;

        foreach ($chunk as $offset => $team) {
            $teamNumber = $processedCount + $offset + 1;
            $identityKey = $this->teamIdentityKey($team->organization);

            $existingTeam = $teamLookupCache[$identityKey] ?? null;
            $teamData = $this->buildTeamDataForPersist($team, $existingTeam);
            $dto = ValidatedTeamData::fromArray($teamData);

            try {
                if ($existingTeam !== null) {
                    $persistedTeam = $this->teamCommandService->update($existingTeam, $dto);
                    $updatedCount++;
                } else {
                    $persistedTeam = $this->teamCommandService->create($dto);
                    $importedCount++;
                }
            } catch (\Throwable $exception) {
                $errors[] = "Skipped team {$teamNumber}: failed to persist imported data.";

                continue;
            }

            // Keep chunk-local duplicates from causing duplicate creates.
            $teamLookupCache[$identityKey] = $persistedTeam;
        }

        return [$importedCount, $updatedCount];
    }

    /**
     * Build a validated payload for create/update while preserving existing values when
     * the incoming import does not explicitly provide a replacement value.
     *
     * For existing teams, this method intentionally performs a non-destructive merge:
     * - scalar fields only change when a non-empty value is provided by the source
     * - list fields (logos/social links) are merged and deduplicated
     * - sports are merged so cross-source imports can accumulate multiple sports
     *
     * @param  ImportedTeamData  $team  The incoming team data from the import source.
     * @param  Team|null  $existingTeam  The existing team record if found, or null if this team is new.
     * @return array<string, mixed> An array of team data ready for validation and persistence.
     */
    private function buildTeamDataForPersist(ImportedTeamData $team, ?Team $existingTeam): array
    {
        // New team: normalize incoming values and persist exactly what the source provides.
        if ($existingTeam === null) {
            return [
                'organization' => $team->organization,
                'designation' => $this->pickPreferredString($team->designation, $team->organization),
                'conference' => $team->conference,
                'abbreviation' => $this->nullableStringOrNull($team->abbreviation),
                'color' => $this->nullableStringOrNull($team->color),
                'logos' => $this->normalizeList($team->logos),
                'social_media' => $this->normalizeList($team->socialMedia),
                'sports' => $this->mergeSportValues(null, $team->sport),
                'sport_conferences' => $this->buildIncomingSportConferenceMap($team->sport, $team->conference),
                'type' => $this->resolveTypeValue($team->type, null),
            ];
        }

        // Existing team: merge data to avoid losing previously imported information.
        return [
            'organization' => $this->pickPreferredString($team->organization, $existingTeam->organization),
            'designation' => $this->pickPreferredString($team->designation, $existingTeam->designation),
            'conference' => $this->pickPreferredString($team->conference, TeamFallback::CONFERENCE->value()),
            'abbreviation' => $this->pickPreferredNullableString($team->abbreviation, $existingTeam->abbreviation),
            'color' => $this->pickPreferredNullableString($team->color, $existingTeam->color),
            'logos' => $this->mergeLists($existingTeam->logos, $team->logos),
            'social_media' => $this->mergeLists($existingTeam->social_media, $team->socialMedia),
            'sports' => $this->mergeSportValues($existingTeam, $team->sport),
            'sport_conferences' => $this->mergeSportConferenceValues($existingTeam, $team->sport, $team->conference),
            'type' => $this->resolveTypeValue($team->type, $existingTeam->type),
        ];
    }

    /**
     * Use the incoming string only when it is non-empty; otherwise keep the fallback.
     */
    private function pickPreferredString(?string $incoming, string $fallback): string
    {
        if ($this->hasValue($incoming)) {
            return trim($incoming);
        }

        return $fallback;
    }

    /**
     * Use the incoming nullable string only when it is non-empty; otherwise keep fallback.
     */
    private function pickPreferredNullableString(?string $incoming, ?string $fallback): ?string
    {
        if ($this->hasValue($incoming)) {
            return trim($incoming);
        }

        return $fallback;
    }

    /**
     * Normalize optional scalar text into a trimmed value or null.
     */
    private function nullableStringOrNull(?string $value): ?string
    {
        if ($this->hasValue($value)) {
            return trim($value);
        }

        return null;
    }

    /**
     * Determine whether a string carries meaningful content.
     */
    private function hasValue(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /**
     * Merge existing and incoming list-like values, preserving existing entries and
     * deduplicating by encoded content to prevent repeated links/assets.
     *
     * @return array<int, mixed>|null
     */
    private function mergeLists(mixed $existing, mixed $incoming): ?array
    {
        $existingList = $this->normalizeList($existing) ?? [];
        $incomingList = $this->normalizeList($incoming);

        if ($incomingList === null) {
            return $existingList !== [] ? $existingList : null;
        }

        $merged = [];
        $seen = [];

        foreach (array_merge($existingList, $incomingList) as $entry) {
            $encoded = json_encode($entry);

            if (! is_string($encoded) || isset($seen[$encoded])) {
                continue;
            }

            $seen[$encoded] = true;
            $merged[] = $entry;
        }

        return $merged !== [] ? $merged : null;
    }

    /**
     * Normalize a potential list value into a dense array or null.
     * Empty strings, null entries, and empty nested arrays are removed.
     *
     * @return array<int, mixed>|null
     */
    private function normalizeList(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter($value, function (mixed $item): bool {
            if (is_string($item)) {
                return trim($item) !== '';
            }

            if (is_array($item)) {
                return $item !== [];
            }

            return $item !== null;
        }));

        return $normalized !== [] ? $normalized : null;
    }

    /**
     * Merge existing team sports with the incoming sport so imports from different
     * sources can enrich the same team instead of replacing prior sport associations.
     *
     * @return array<int, string>
     */
    private function mergeSportValues(?Team $existingTeam, ?string $incomingSport): array
    {
        $sports = [];

        if ($existingTeam !== null) {
            $sports = $existingTeam->sports()
                ->pluck('sport')
                ->map(fn (mixed $sport): string => $sport instanceof Sport ? $sport->value : (string) $sport)
                ->filter(fn (string $sport): bool => trim($sport) !== '')
                ->values()
                ->all();
        }

        if ($this->hasValue($incomingSport)) {
            $sports[] = trim($incomingSport);
        }

        return array_values(array_unique($sports));
    }

    /**
     * Build a map of sport => conference for incoming data.
     *
     * @return array<string, string>
     */
    private function buildIncomingSportConferenceMap(?string $sport, ?string $conference): array
    {
        if (! $this->hasValue($sport)) {
            return [];
        }

        $normalizedSport = trim($sport);
        $normalizedConference = $this->hasValue($conference)
            ? trim((string) $conference)
            : TeamFallback::CONFERENCE->value();

        return [$normalizedSport => $normalizedConference];
    }

    /**
     * Merge existing sport-conference mappings with the incoming mapping.
     *
     * @return array<string, string>
     */
    private function mergeSportConferenceValues(Team $existingTeam, ?string $incomingSport, ?string $incomingConference): array
    {
        $existingMapping = $existingTeam->sports()
            ->get(['sport', 'conference'])
            ->mapWithKeys(function ($teamSport): array {
                $sportValue = $teamSport->sport instanceof Sport
                    ? $teamSport->sport->value
                    : (string) $teamSport->sport;

                $conference = trim((string) $teamSport->conference);
                if ($conference === '') {
                    $conference = TeamFallback::CONFERENCE->value();
                }

                return [$sportValue => $conference];
            })
            ->all();

        return array_merge($existingMapping, $this->buildIncomingSportConferenceMap($incomingSport, $incomingConference));
    }

    /**
     * Resolve the team type from incoming data, falling back to an existing type when
     * the source omits or sends an invalid value.
     */
    private function resolveTypeValue(?string $incomingType, ?string $fallbackType): string
    {
        if ($this->hasValue($incomingType)) {
            try {
                return TeamType::from(trim($incomingType))->value;
            } catch (\ValueError) {
                // Fall through to existing type if source provided an invalid value.
            }
        }

        return $fallbackType ?? TeamType::COLLEGE->value;
    }

    /**
     * Resolves a source key to the configured team import source implementation.
     *
     * @param  string  $key  The unique key identifying the desired team import source.
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
     * @param  TeamImportData  $data  The data transfer object containing import options, including an optional 'chunk_size'.
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
     * Preloads teams matching identity combinations represented in the chunk into
     * the lookup cache, minimizing database queries during the chunk import.
     *
     * @param  array<int, ImportedTeamData>  $teams
     * @param  array<string, Team>  $teamLookupCache
     */
    private function warmTeamLookupCache(array $teams, array &$teamLookupCache): void
    {
        $missingByIdentity = [];
        $requestedOrganizations = [];

        foreach ($teams as $team) {
            $identityKey = $this->teamIdentityKey($team->organization);

            if (! isset($teamLookupCache[$identityKey])) {
                $missingByIdentity[$identityKey] = true;
                $requestedOrganizations[] = trim($team->organization);
            }
        }

        if ($missingByIdentity === []) {
            return;
        }

        $organizationValues = array_values(array_unique(array_filter(
            $requestedOrganizations,
            fn (string $organization): bool => $organization !== '',
        )));

        if ($organizationValues === []) {
            return;
        }

        $matches = Team::query()
            ->whereIn(DB::raw('LOWER(organization)'), array_map(fn (string $organization): string => Str::lower($organization), $organizationValues))
            ->get();

        foreach ($matches as $matchedTeam) {
            $identityKey = $this->teamIdentityKey($matchedTeam->organization);

            if (! isset($teamLookupCache[$identityKey])) {
                $teamLookupCache[$identityKey] = $matchedTeam;
            }
        }
    }

    /**
     * Build a normalized identity key from organization.
     */
    private function teamIdentityKey(?string $organization): string
    {
        return Str::lower(trim((string) $organization));
    }
}
