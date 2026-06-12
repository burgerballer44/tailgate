<?php

namespace App\Services\ImportSources;

use App\Clients\CFBDApiClient;
use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Exceptions\GameImportException;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use App\Services\Contracts\GameImportSourceInterface;
use App\Traits\ImportSourceDataHelpers;
use Carbon\CarbonImmutable;
use Throwable;

class CFBDGameImportSource implements GameImportSourceInterface
{
    use ImportSourceDataHelpers;

    public function __construct(private readonly CFBDApiClient $client) {}

    public function key(): string
    {
        return 'cfbd';
    }

    public function label(): string
    {
        return 'CFBD API';
    }

    public function type(): string
    {
        return 'api';
    }

    public function description(): string
    {
        return 'Imports football schedules from CollegeFootballData.';
    }

    /**
     * @return ImportFetchStream<ImportedGameData>
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
            'seasonType' => $data->options['season_type'] ?? null,
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
                    $homeTeamConference = (string) $this->valueForAny($rawGame, ['homeConference', 'home_conference'], Team::UNKNOWN_CONFERENCE);
                    $homeTeamScore = (int) $this->valueForAny($rawGame, ['homePoints', 'home_points'], default: 0);

                    // get values for away team
                    $awayTeam = $this->valueForAny($rawGame, ['awayTeam', 'away_team'], null);
                    $awayTeamConference = (string) $this->valueForAny($rawGame, ['awayConference', 'away_conference'], Team::UNKNOWN_CONFERENCE);
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
}
