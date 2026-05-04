<?php

namespace App\Services;

use App\DTO\GameImportData;
use App\DTO\ImportResult;
use App\DTO\ValidatedGameData;
use App\Exceptions\GameImportException;
use App\Models\Season;
use App\Models\Team;
use App\Services\Contracts\GameImportManagerInterface;
use App\Services\Contracts\GameImportSourceInterface;
use App\Services\Contracts\SeasonCommandInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameImportManager implements GameImportManagerInterface
{
    private const DEFAULT_IMPORT_CHUNK_SIZE = 500;

    /**
     * @param  iterable<int, GameImportSourceInterface>  $sources
     */
    public function __construct(
        private readonly SeasonCommandInterface $seasonCommandService,
        private readonly iterable $sources,
    ) {}

    /**
     * Retrieves a list of available game import sources.
     *
     * @return array<int, array<string, string>> An array of available import sources with their keys, labels, descriptions, and types.
     */
    public function availableSources(): array
    {
        return collect($this->sources)
            ->map(fn (GameImportSourceInterface $source): array => [
                'value' => $source->key(),
                'label' => $source->label(),
                'description' => $source->description(),
                'type' => $source->type(),
            ])
            ->values()
            ->all();
    }

    public function import(Season $season, GameImportData $data): ImportResult
    {
        $source = $this->resolveSource($data->source);
        $fetchStream = $source->fetch($season, $data);
        $importErrors = [];
        $importedCount = 0;

        $processedCount = 0;
        $chunkSize = $this->resolveChunkSize($data);
        $chunk = [];
        $teamLookupCache = [];
        
        // process the fetch stream in chunks to manage memory usage and allow for batch processing of games
        foreach ($fetchStream->items() as $game) {
            $chunk[] = $game;

            if (count($chunk) === $chunkSize) {
                $importedCount += $this->importChunk($season, $chunk, $processedCount, $importErrors, $teamLookupCache);
                $processedCount += $chunkSize;
                $chunk = [];
            }
        }

        // process any remaining items in the chunk that didn't reach the chunk size threshold
        if ($chunk !== []) {
            $importedCount += $this->importChunk($season, $chunk, $processedCount, $importErrors, $teamLookupCache);
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
     * Resolves the import source based on the provided key.
     *
     * @param string $key The key of the import source to resolve.
     * @return GameImportSourceInterface The resolved import source instance.
     * @throws GameImportException If the specified import source is not found.
     */
    private function resolveSource(string $key): GameImportSourceInterface
    {
        foreach ($this->sources as $source) {
            if ($source->key() === $key) {
                return $source;
            }
        }

        throw new GameImportException('Selected import source is not available.');
    }

    /**
     * Determines the chunk size for processing the import based on the provided data options or defaults.
     *
     * @param GameImportData $data The game import data containing options for the import process.
     * @return int The resolved chunk size to use for processing the import.
     */
    private function resolveChunkSize(GameImportData $data): int
    {
        $chunkSize = $data->options['chunk_size'] ?? self::DEFAULT_IMPORT_CHUNK_SIZE;

        return is_int($chunkSize) && $chunkSize > 0
            ? $chunkSize
            : self::DEFAULT_IMPORT_CHUNK_SIZE;
    }

    /**
     * Imports a chunk of games into the specified season, handling team resolution, duplicate detection, and error collection.
     *
     * @param Season $season The season to which the games should be added.
     * @param array<int, \App\DTO\ImportedGameData> $chunk The chunk of imported game data to process.
     * @param int $processedCount The count of games processed before this chunk, used for error messaging.
     * @param array<int, string> $errors A reference to an array where import errors will be collected.
     * @param array<string, array<string, Team>> $teamLookupCache A reference to a cache for team lookups to optimize performance.
     * @return int The count of successfully imported games from this chunk.
     */
    private function importChunk(Season $season, array $chunk, int $processedCount, array &$errors, array &$teamLookupCache): int
    {
        $this->warmTeamLookupCache($season, $chunk, $teamLookupCache);

        $resolvedGames = [];

        foreach ($chunk as $offset => $game) {
            $gameNumber = $processedCount + $offset + 1;
            $homeTeam = $this->resolveTeamFromLookup($teamLookupCache, $game->homeTeam);
            $awayTeam = $this->resolveTeamFromLookup($teamLookupCache, $game->awayTeam);

            if ($homeTeam === null) {
                $errors[] = "Skipped game {$gameNumber}: home team '{$game->homeTeam}' was not found for this season's sport.";

                continue;
            }

            if ($awayTeam === null) {
                $errors[] = "Skipped game {$gameNumber}: away team '{$game->awayTeam}' was not found for this season's sport.";

                continue;
            }

            if ($homeTeam->is($awayTeam)) {
                $errors[] = "Skipped game {$gameNumber}: home and away teams resolved to the same team.";

                continue;
            }

            $kickoff = CarbonImmutable::parse($game->startDateTime);

            $resolvedGames[] = [
                'game_number' => $gameNumber,
                'home_team_id' => $homeTeam->id,
                'away_team_id' => $awayTeam->id,
                'home_team_score' => $game->homeTeamScore,
                'away_team_score' => $game->awayTeamScore,
                'start_date_time' => $kickoff->toDateTimeString(),
                'start_time_tbd' => $game->startTimeTBD,
            ];
        }

        if ($resolvedGames === []) {
            return 0;
        }

        $existingSignatures = $this->existingGameSignatures($season, $resolvedGames);
        $importedCount = 0;

        foreach ($resolvedGames as $resolvedGame) {
            $signature = $this->gameSignature(
                $resolvedGame['home_team_id'],
                $resolvedGame['away_team_id'],
                $resolvedGame['start_date_time'],
            );

            if (isset($existingSignatures[$signature])) {
                $errors[] = "Skipped game {$resolvedGame['game_number']}: an identical game already exists in this season.";

                continue;
            }

            $this->seasonCommandService->addGame($season, ValidatedGameData::fromArray([
                'season_id' => $season->id,
                'home_team_id' => $resolvedGame['home_team_id'],
                'away_team_id' => $resolvedGame['away_team_id'],
                'home_team_score' => $resolvedGame['home_team_score'],
                'away_team_score' => $resolvedGame['away_team_score'],
                'start_date_time' => $resolvedGame['start_date_time'],
                'start_time_tbd' => $resolvedGame['start_time_tbd'],
            ]));

            $existingSignatures[$signature] = true;
            $importedCount++;
        }

        return $importedCount;
    }

    /**
     * Warms the team lookup cache for the given season and chunk of imported games.
     *
     * @param Season $season The season for which to warm the team lookup cache.
     * @param array<int, \App\DTO\ImportedGameData> $chunk The chunk of imported game data.
     * @param array<string, array<string, Team>> $teamLookupCache A reference to the team lookup cache.
     */
    private function warmTeamLookupCache(Season $season, array $chunk, array &$teamLookupCache): void
    {
        $conferenceValues = [];

        foreach ($chunk as $game) {
            // extract and normalize conference values from the home and away teams in the chunk to optimize the team query
            $homeConference = Str::lower(trim($game->homeTeamConference));
            $awayConference = Str::lower(trim($game->awayTeamConference));

            if ($homeConference !== '') {
                $conferenceValues[$homeConference] = true;
            }

            if ($awayConference !== '') {
                $conferenceValues[$awayConference] = true;
            }
        }

        // generate a cache key based on the conference values represented in the chunk to allow for caching team lookups across chunks with the same conference representation
        $conferenceKeys = array_keys($conferenceValues);
        sort($conferenceKeys);
        $cacheKey = $conferenceKeys === [] ? '*' : implode('|', $conferenceKeys);

        if (isset($teamLookupCache[$cacheKey])) {
            return;
        }

        // query teams for this season's sport and any conferences represented in the chunk to build a lookup cache for resolving team names to IDs during the import process
        $query = Team::query()
            ->whereHas('sports', fn ($builder) => $builder->where('sport', $season->sport));

        if ($conferenceKeys !== []) {
            $query->whereIn(DB::raw('LOWER(conference)'), $conferenceKeys);
        }

        $lookup = [];

        // for each team, generate candidate strings for matching based on organization and designation,
        // then normalize and store them in the lookup cache to allow for flexible team name resolution during the import process
        foreach ($query->get() as $team) {
            $candidates = array_filter([
                $team->organization,
                $team->designation,
                trim(implode(' ', array_filter([$team->organization, $team->designation]))),
            ]);

            foreach ($candidates as $candidate) {
                $normalized = $this->normalizeTeamName($candidate);

                if (! isset($lookup[$normalized])) {
                    $lookup[$normalized] = $team;
                }
            }
        }

        $teamLookupCache[$cacheKey] = $lookup;
    }

    /**
     * Resolves a team from the lookup cache based on the given team name.
     *
     * @param array<string, array<string, Team>> $teamLookupCache The team lookup cache.
     * @param string $teamName The name of the team to resolve.
     * @return Team|null The resolved team or null if not found.
     */
    private function resolveTeamFromLookup(array $teamLookupCache, string $teamName): ?Team
    {
        $normalizedTeamName = $this->normalizeTeamName($teamName);

        foreach ($teamLookupCache as $lookup) {
            if (isset($lookup[$normalizedTeamName])) {
                return $lookup[$normalizedTeamName];
            }
        }

        return null;
    }

    /**
     * Retrieves existing games for the given season that match any of the home team IDs, away team IDs, and start date-times from the resolved games.
     * This is used to identify duplicate games during the import process.
     *
     * @param Season $season The season for which to check existing games.
     * @param array<int, array<string, mixed>> $resolvedGames The resolved games with home team IDs, away team IDs, and start date-times.
     * @return array<string, bool> An associative array where keys are game signatures and values indicate existence.
     */
    private function existingGameSignatures(Season $season, array $resolvedGames): array
    {
        $homeTeamIds = array_values(array_unique(array_column($resolvedGames, 'home_team_id')));
        $awayTeamIds = array_values(array_unique(array_column($resolvedGames, 'away_team_id')));
        $startDateTimes = array_values(array_unique(array_column($resolvedGames, 'start_date_time')));

        if ($homeTeamIds === [] || $awayTeamIds === [] || $startDateTimes === []) {
            return [];
        }

        $existing = [];

        foreach ($season->games()
            ->select(['home_team_id', 'away_team_id', 'start_date_time'])
            ->whereIn('home_team_id', $homeTeamIds)
            ->whereIn('away_team_id', $awayTeamIds)
            ->whereIn('start_date_time', $startDateTimes)
            ->get() as $game
        ) {
            $signature = $this->gameSignature(
                $game->home_team_id,
                $game->away_team_id,
                (string) $game->start_date_time,
            );

            $existing[$signature] = true;
        }

        return $existing;
    }

    /**
     * Generates a unique signature for a game based on its home team ID, away team ID, and start date-time.
     * This is used to identify duplicate games during the import process.
     *
     * @param int $homeTeamId The ID of the home team.
     * @param int $awayTeamId The ID of the away team.
     * @param string $startDateTime The start date-time of the game.
     * @return string A unique signature string representing the game.
     */
    private function gameSignature(int $homeTeamId, int $awayTeamId, string $startDateTime): string
    {
        return $homeTeamId.'|'.$awayTeamId.'|'.$startDateTime;
    }

    /**
     * Normalizes a team name by trimming whitespace and converting to lowercase.
     *
     * @param string $teamName The team name to normalize.
     * @return string The normalized team name.
     */
    private function normalizeTeamName(string $teamName): string
    {
        return mb_strtolower(trim($teamName));
    }
}