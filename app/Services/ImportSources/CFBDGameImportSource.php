<?php

namespace App\Services\ImportSources;

use App\Clients\CFBDApiClient;
use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Exceptions\GameImportException;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use App\Models\Enums\TeamFallback;
use App\Models\Season;
use App\Services\Contracts\GameImportSourceInterface;
use App\Traits\ImportSourceDataHelpers;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Supplies football schedule data from the CFBD API in the normalized game import shape.
 * Enforces sport compatibility and maps source payloads into application-ready game records.
 */
class CFBDGameImportSource implements GameImportSourceInterface
{
    use ImportSourceDataHelpers;

    public function __construct(private readonly CFBDApiClient $client) {}

    /**
     * Get the stable import key used to select this CFBD source.
     *
     * @return string The source key consumed by import managers and forms.
     */
    public function key(): string
    {
        return 'cfbd';
    }

    /**
     * Get the human-readable source label.
     *
     * @return string The display label for this source.
     */
    public function label(): string
    {
        return 'CFBD API';
    }

    /**
     * Identify this source as API-backed.
     *
     * @return string The source transport type used for display and grouping.
     */
    public function type(): string
    {
        return 'api';
    }

    /**
     * Describe this source's feed and expected data.
     *
     * @return string A short description of the CFBD football schedule feed.
     */
    public function description(): string
    {
        return 'Imports football schedules from CollegeFootballData.';
    }

    /**
     * Stream normalized CFBD game records for the requested season and import options.
     *
     * @param  Season  $season  The season whose sport constrains which games can be imported.
     * @param  GameImportData  $data  Import options including year, season type, and week filters.
     * @return ImportFetchStream<ImportedGameData> A stream of normalized game DTOs plus any fetch-time errors.
     *
     * @throws GameImportException When the season is not a football season.
     */
    public function fetch(Season $season, GameImportData $data): ImportFetchStream
    {
        // CFBD only supports football, so throw an exception if this season is for a different sport
        if ($season->sport !== Sport::FOOTBALL->value) {
            throw new GameImportException('CFBD imports are only available for football seasons.');
        }

        // get the game stream from the CFBD API using the provided query options
        $rawGameStream = $this->client->fetchGames([
            'year' => $data->options['year'] ?? null,
            'seasonType' => $this->translateSeasonTypeForClient($data->options['season_type'] ?? null),
            'week' => $data->options['week'] ?? null,
        ]);

        return new ImportFetchStream(
            (function () use ($rawGameStream): \Generator {
                $errors = [];

                foreach ($rawGameStream as $index => $rawGame) {
                    // in case of an issue keep track of the game id
                    $gameRow = $index + 1;
                    $gameId = $this->valueForAny($rawGame, ['id'], $gameRow);

                    // get values for home team
                    $homeTeam = $this->valueForAny($rawGame, ['homeTeam', 'home_team'], null);
                    $homeTeamConference = (string) $this->valueForAny($rawGame, ['homeConference', 'home_conference'], TeamFallback::CONFERENCE->value());
                    $homeTeamScore = (int) $this->valueForAny($rawGame, ['homePoints', 'home_points'], default: 0);

                    // get values for away team
                    $awayTeam = $this->valueForAny($rawGame, ['awayTeam', 'away_team'], null);
                    $awayTeamConference = (string) $this->valueForAny($rawGame, ['awayConference', 'away_conference'], TeamFallback::CONFERENCE->value());
                    $awayTeamScore = (int) $this->valueForAny($rawGame, ['awayPoints', 'away_points'], default: 0);

                    // get the start date and time for the game, ensuring we have a valid date to work with
                    $startDateTime = $this->valueForAny($rawGame, ['startDate', 'start_date'], null);
                    $startTimeTBD = (bool) $this->valueForAny($rawGame, ['startTimeTBD', 'start_time_tbd'], false);

                    // if we don't have teams or a start date, we can't import this game, so add an error and skip it
                    if (! is_string($homeTeam) || ! is_string($awayTeam) || ! is_string($startDateTime)) {
                        $errors[] = "Skipped CFBD game {$gameId}: required fields were missing from the response.";

                        continue;
                    }

                    // make sure we have a valid kickoff time - if the time is TBD, it can be set later
                    try {
                        $kickoff = CarbonImmutable::parse($startDateTime);
                    } catch (Throwable) {
                        $errors[] = "Skipped CFBD game {$gameId}: start date '{$startDateTime}' could not be parsed.";

                        continue;
                    }

                    yield new ImportedGameData(
                        homeTeam: trim($homeTeam),
                        homeTeamConference: trim($homeTeamConference),
                        awayTeam: trim($awayTeam),
                        awayTeamConference: trim($awayTeamConference),
                        homeTeamScore: $homeTeamScore,
                        awayTeamScore: $awayTeamScore,
                        startDateTime: $kickoff->toDateTimeString(),
                        startTimeTBD: $startTimeTBD,
                    );
                }

                return $errors;
            })(),
        );
    }

    /**
     * Translates application season types into the values expected by the CFBD API.
     *
     * @param  mixed  $seasonType  The incoming season type value from import options.
     * @return string|null The CFBD season type string, or null when no translation applies.
     */
    private function translateSeasonTypeForClient(mixed $seasonType): ?string
    {
        if ($seasonType instanceof SeasonType) {
            $seasonType = $seasonType->value;
        }

        if (! is_string($seasonType) || blank($seasonType)) {
            return null;
        }

        return match ($seasonType) {
            SeasonType::REGULAR->value => 'regular',
            default => $seasonType,
        };
    }
}
