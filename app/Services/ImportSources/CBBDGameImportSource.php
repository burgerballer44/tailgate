<?php

namespace App\Services\ImportSources;

use App\Clients\CBBDApiClient;
use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Exceptions\GameImportException;
use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use App\Models\Team;
use App\Services\Contracts\GameImportSourceInterface;
use App\Traits\ImportSourceDataHelpers;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Supplies basketball schedule data from the CBBD API in the normalized game import shape.
 * Enforces sport compatibility and maps source payloads into application-ready game records.
 */
class CBBDGameImportSource implements GameImportSourceInterface
{
    use ImportSourceDataHelpers;

    public function __construct(private readonly CBBDApiClient $client) {}

    public function key(): string
    {
        return 'cbbd';
    }

    public function label(): string
    {
        return 'CBBD API';
    }

    public function type(): string
    {
        return 'api';
    }

    public function description(): string
    {
        return 'Imports basketball schedules from CollegeBasketballData.';
    }

    /**
     * @return ImportFetchStream<ImportedGameData>
     */
    public function fetch(Season $season, GameImportData $data): ImportFetchStream
    {
        if ($season->sport !== Sport::BASKETBALL->value) {
            throw new GameImportException('CBBD imports are only available for basketball seasons.');
        }

        $rawGameStream = $this->client->fetchGames([
            'year' => $data->options['year'] ?? null,
            'seasonType' => $this->translateSeasonTypeForClient($data->options['season_type'] ?? null),
            'conference' => $data->options['conference'] ?? null,
            'team' => $data->options['team'] ?? null,
            'startDate' => $data->options['start_date'] ?? null,
            'endDate' => $data->options['end_date'] ?? null,
        ]);

        return new ImportFetchStream(
            (function () use ($rawGameStream): \Generator {
                $errors = [];

                foreach ($rawGameStream as $index => $rawGame) {
                    $gameRow = $index + 1;
                    $gameId = $this->valueForAny($rawGame, ['id', 'gameId', 'game_id'], $gameRow);

                    $homeTeam = $this->valueForAny($rawGame, ['homeTeam', 'home_team'], null);
                    $homeTeamConference = (string) $this->valueForAny(
                        $rawGame,
                        ['homeConference', 'home_conference'],
                        Team::UNKNOWN_CONFERENCE,
                    );
                    $homeTeamScore = (int) $this->valueForAny(
                        $rawGame,
                        ['homePoints', 'home_points', 'homeScore', 'home_score'],
                        0,
                    );

                    $awayTeam = $this->valueForAny($rawGame, ['awayTeam', 'away_team'], null);
                    $awayTeamConference = (string) $this->valueForAny(
                        $rawGame,
                        ['awayConference', 'away_conference'],
                        Team::UNKNOWN_CONFERENCE,
                    );
                    $awayTeamScore = (int) $this->valueForAny(
                        $rawGame,
                        ['awayPoints', 'away_points', 'awayScore', 'away_score'],
                        0,
                    );

                    $startDateTime = $this->valueForAny(
                        $rawGame,
                        ['startDate', 'start_date', 'date'],
                        null,
                    );
                    $startTimeTBD = (bool) $this->valueForAny(
                        $rawGame,
                        ['startTimeTBD', 'start_time_tbd'],
                        false,
                    );

                    if (! is_string($homeTeam) || ! is_string($awayTeam) || ! is_string($startDateTime)) {
                        $errors[] = "Skipped CBBD game {$gameId}: required fields were missing from the response.";

                        continue;
                    }

                    try {
                        $kickoff = CarbonImmutable::parse($startDateTime);
                    } catch (Throwable) {
                        $errors[] = "Skipped CBBD game {$gameId}: start date '{$startDateTime}' could not be parsed.";

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
     * Translates application season types into the values expected by the CBBD API.
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
            SeasonType::POST->value => 'postseason',
            default => $seasonType,
        };
    }
}
