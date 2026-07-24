<?php

use App\Clients\CBBDApiClient;
use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Exceptions\GameImportException;
use App\Models\Season;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use App\Services\ImportSources\CBBDGameImportSource;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->client = Mockery::mock(CBBDApiClient::class);
    $this->source = new CBBDGameImportSource($this->client);
});

function cbbdSeasonWithSport(Sport $sport): Season
{
    $season = new Season;
    $season->sport = $sport->value;

    return $season;
}

function CBBDImportData(array $options = []): GameImportData
{
    return new GameImportData(
        source: 'cbbd',
        options: $options ?: ['year' => 2024, 'season_type' => SeasonType::REGULAR->value],
    );
}

function cbbdSampleGame(array $overrides = []): array
{
    return array_merge([
        'id' => 9001,
        'startDate' => '2026-11-10T02:30:00.000Z',
        'startTimeTBD' => false,
        'homeTeam' => 'Duke',
        'homeConference' => 'ACC',
        'homePoints' => 74,
        'awayTeam' => 'North Carolina',
        'awayConference' => 'ACC',
        'awayPoints' => 70,
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return Generator<int, array<string, mixed>>
 */
function cbbdGameRowStream(array $rows): Generator
{
    foreach ($rows as $row) {
        yield $row;
    }
}

/**
 * @return object{games: list<ImportedGameData>, errors: list<string>}
 */
function collectCBBDGameStream(ImportFetchStream $stream): object
{
    $games = iterator_to_array($stream->items(), preserve_keys: false);
    $errors = $stream->errors();

    return (object) compact('games', 'errors');
}

describe('identity methods', function () {
    test('key returns cbbd', function () {
        expect($this->source->key())->toBe('cbbd');
    });

    test('label returns CBBD API', function () {
        expect($this->source->label())->toBe('CBBD API');
    });

    test('type returns api', function () {
        expect($this->source->type())->toBe('api');
    });

    test('description returns description', function () {
        expect($this->source->description())->toBe('Imports basketball schedules from CollegeBasketballData.');
    });
});

describe('fetch - sport guard', function () {
    test('throws GameImportException when the season sport is not basketball', function () {
        $season = cbbdSeasonWithSport(Sport::FOOTBALL);

        $this->source->fetch($season, CBBDImportData());
    })->throws(GameImportException::class, 'CBBD imports are only available for basketball seasons.');

    test('does not throw for a basketball season', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);

        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([]));

        $this->source->fetch($season, CBBDImportData());
    })->throwsNoExceptions();
});

describe('fetch - API client query parameters', function () {
    test('translates Regular Season to the CBBD regular season slug before calling the client', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $data = CBBDImportData([
            'year' => 2025,
            'season_type' => SeasonType::REGULAR->value,
            'conference' => 'ACC',
            'team' => 'Duke',
            'start_date' => '2025-11-01',
            'end_date' => '2025-12-01',
        ]);

        $this->client
            ->shouldReceive('fetchGames')
            ->once()
            ->with([
                'year' => 2025,
                'seasonType' => 'regular',
                'conference' => 'ACC',
                'team' => 'Duke',
                'startDate' => '2025-11-01',
                'endDate' => '2025-12-01',
            ])
            ->andReturn(cbbdGameRowStream([]));

        $this->source->fetch($season, $data);
    });

    test('passes unsupported season type through unchanged before calling the client', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $data = CBBDImportData([
            'year' => 2025,
            'season_type' => 'Custom Season Type',
            'conference' => 'ACC',
            'team' => 'Duke',
            'start_date' => '2025-11-01',
            'end_date' => '2025-12-01',
        ]);

        $this->client
            ->shouldReceive('fetchGames')
            ->once()
            ->with([
                'year' => 2025,
                'seasonType' => 'Custom Season Type',
                'conference' => 'ACC',
                'team' => 'Duke',
                'startDate' => '2025-11-01',
                'endDate' => '2025-12-01',
            ])
            ->andReturn(cbbdGameRowStream([]));

        $this->source->fetch($season, $data);
    });

    test('passes null for missing options', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $data = new GameImportData(source: 'cbbd', options: []);

        $this->client
            ->shouldReceive('fetchGames')
            ->once()
            ->with([
                'year' => null,
                'seasonType' => null,
                'conference' => null,
                'team' => null,
                'startDate' => null,
                'endDate' => null,
            ])
            ->andReturn(cbbdGameRowStream([]));

        $this->source->fetch($season, $data);
    });
});

describe('fetch - successful mapping', function () {
    test('maps camelCase payload into ImportedGameData DTO', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([cbbdSampleGame()]));

        $result = collectCBBDGameStream($this->source->fetch($season, CBBDImportData()));

        expect($result->games)->toHaveCount(1)
            ->and($result->games[0])->toBeInstanceOf(ImportedGameData::class)
            ->and($result->games[0]->homeTeam)->toBe('Duke')
            ->and($result->games[0]->homeTeamConference)->toBe('ACC')
            ->and($result->games[0]->awayTeam)->toBe('North Carolina')
            ->and($result->games[0]->awayTeamConference)->toBe('ACC')
            ->and($result->games[0]->homeTeamScore)->toBe(74)
            ->and($result->games[0]->awayTeamScore)->toBe(70)
            ->and($result->games[0]->startTimeTBD)->toBeFalse();
    });

    test('accepts snake_case score keys and date fallback key', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([[
            'homeTeam' => 'Gonzaga',
            'awayTeam' => 'Baylor',
            'homeConference' => 'WCC',
            'awayConference' => 'Big 12',
            'home_score' => 68,
            'away_score' => 66,
            'date' => '2025-03-20T17:00:00.000Z',
        ]]));

        $result = collectCBBDGameStream($this->source->fetch($season, CBBDImportData()));

        expect($result->games)->toHaveCount(1)
            ->and($result->games[0]->homeTeamScore)->toBe(68)
            ->and($result->games[0]->awayTeamScore)->toBe(66)
            ->and($result->games[0]->startDateTime)->toContain('2025-03-20');
    });

    test('trims surrounding whitespace from team names and conferences', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([cbbdSampleGame([
            'homeTeam' => '  Duke  ',
            'homeConference' => ' ACC ',
            'awayTeam' => '  North Carolina  ',
            'awayConference' => ' ACC ',
        ])]));

        $result = collectCBBDGameStream($this->source->fetch($season, CBBDImportData()));

        expect($result->games[0]->homeTeam)->toBe('Duke')
            ->and($result->games[0]->homeTeamConference)->toBe('ACC')
            ->and($result->games[0]->awayTeam)->toBe('North Carolina')
            ->and($result->games[0]->awayTeamConference)->toBe('ACC');
    });

    test('defaults scores to zero when score fields are missing', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([[
            'homeTeam' => 'UConn',
            'awayTeam' => 'Purdue',
            'startDate' => '2025-04-08T00:10:00.000Z',
        ]]));

        $result = collectCBBDGameStream($this->source->fetch($season, CBBDImportData()));

        expect($result->games[0]->homeTeamScore)->toBe(0)
            ->and($result->games[0]->awayTeamScore)->toBe(0);
    });
});

describe('fetch - skip logic', function () {
    test('skips rows with missing required fields and keeps valid rows', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([
            cbbdSampleGame(['homeTeam' => null]),
            cbbdSampleGame(),
            cbbdSampleGame(['awayTeam' => null, 'id' => 9003]),
            cbbdSampleGame(['startDate' => null, 'id' => 9004]),
        ]));

        $result = collectCBBDGameStream($this->source->fetch($season, CBBDImportData()));

        expect($result->games)->toHaveCount(1)
            ->and($result->errors)->toHaveCount(3)
            ->and($result->errors[0])->toContain('CBBD game')
            ->and($result->errors[0])->toContain('required fields were missing from the response');
    });

    test('skips a game when date cannot be parsed', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);
        $this->client->allows('fetchGames')->andReturn(cbbdGameRowStream([
            cbbdSampleGame(['startDate' => 'not-a-date']),
        ]));

        $result = collectCBBDGameStream($this->source->fetch($season, CBBDImportData()));

        expect($result->games)->toBeEmpty()
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain("start date 'not-a-date' could not be parsed");
    });
});

describe('fetch - client exceptions', function () {
    test('propagates a GameImportException thrown by the client', function () {
        $season = cbbdSeasonWithSport(Sport::BASKETBALL);

        $this->client
            ->allows('fetchGames')
            ->andThrow(new GameImportException('CBBD API credentials are not configured.'));

        $this->source->fetch($season, CBBDImportData());
    })->throws(GameImportException::class, 'CBBD API credentials are not configured.');
});
