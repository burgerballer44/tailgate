<?php

use App\Clients\CFBDApiClient;
use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Exceptions\GameImportException;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use App\Models\Season;
use App\Services\ImportSources\CFBDGameImportSource;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // use a mock client to avoid making real API calls during tests
    $this->client = Mockery::mock(CFBDApiClient::class);

    // instantiate the source with the mock client
    $this->source = new CFBDGameImportSource($this->client);
});

/**
 * Create a Season model instance with the given sport without hitting the database.
 */
function seasonWithSport(Sport $sport): Season
{
    $season = new Season;
    $season->sport = $sport->value;

    return $season;
}

/**
 * Minimal GameImportData for tests that do not care about options.
 */
function CFBDImportData(array $options = []): GameImportData
{
    return new GameImportData(
        source: 'cfbd',
        options: $options ?: ['year' => 2024, 'season_type' => SeasonType::REGULAR->value, 'week' => null],
    );
}

/**
 * Minimal valid raw game array as returned by the CFBD API.
 */
function sampleGame(array $overrides = []): array
{
    return array_merge([
        'id' => 401858201,
        'season' => 2026,
        'week' => 1,
        'seasonType' => 'regular',
        'startDate' => '2026-08-29T04:00:00.000Z',
        'startTimeTBD' => true,
        'completed' => false,
        'neutralSite' => false,
        'conferenceGame' => false,
        'attendance' => null,
        'venueId' => 3940,
        'venue' => 'Stanford Stadium',
        'homeId' => 24,
        'homeTeam' => 'Stanford',
        'homeClassification' => 'fbs',
        'homeConference' => 'ACC',
        'homePoints' => 21,
        'homeLineScores' => null,
        'homePostgameWinProbability' => null,
        'homePregameElo' => null,
        'homePostgameElo' => null,
        'awayId' => 62,
        'awayTeam' => "Hawai'i",
        'awayClassification' => 'fbs',
        'awayConference' => 'Mountain West',
        'awayPoints' => 14,
        'awayLineScores' => null,
        'awayPostgameWinProbability' => null,
        'awayPregameElo' => null,
        'awayPostgameElo' => null,
        'excitementIndex' => null,
        'highlights' => '',
        'notes' => null,
    ], $overrides);
}

/**
 * @param  list<array<string, mixed>>  $rows
 * @return Generator<int, array<string, mixed>>
 */
function gameRowStream(array $rows): Generator
{
    foreach ($rows as $row) {
        yield $row;
    }
}

/**
 * Collects an ImportFetchStream into plain arrays for assertion.
 *
 * @return object{games: list<ImportedGameData>, errors: list<string>}
 */
function collectGameStream(ImportFetchStream $stream): object
{
    $games = iterator_to_array($stream->items(), preserve_keys: false);
    $errors = $stream->errors();

    return (object) compact('games', 'errors');
}

describe('identity methods', function () {
    test('key returns cfbd', function () {
        expect($this->source->key())->toBe('cfbd');
    });

    test('label returns CFBD API', function () {
        expect($this->source->label())->toBe('CFBD API');
    });

    test('type returns api', function () {
        expect($this->source->type())->toBe('api');
    });

    test('description returns description', function () {
        expect($this->source->description())->toBe('Imports football schedules from CollegeFootballData.');
    });
});

// ─────────────────────────────────────────────────────────
// fetch – sport guard
// ─────────────────────────────────────────────────────────

describe('fetch – sport guard', function () {
    test('throws GameImportException when the season sport is not football', function () {
        $season = seasonWithSport(Sport::BASKETBALL);

        $this->source->fetch($season, CFBDImportData());
    })->throws(GameImportException::class, 'CFBD imports are only available for football seasons.');

    test('does not throw for a football season', function () {
        $season = seasonWithSport(Sport::FOOTBALL);

        $this->client->allows('fetchGames')->andReturn(gameRowStream([]));

        $this->source->fetch($season, CFBDImportData());
    })->throwsNoExceptions();
});

// ─────────────────────────────────────────────────────────
// fetch – API client query parameters
// ─────────────────────────────────────────────────────────

describe('fetch – API client query parameters', function () {
    test('translates Regular Season to the CFBD regular season slug before calling the client', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $data = CFBDImportData(['year' => 2023, 'season_type' => SeasonType::REGULAR->value, 'week' => 4]);

        $this->client
            ->shouldReceive('fetchGames')
            ->once()
            ->with(['year' => 2023, 'seasonType' => 'regular', 'week' => 4])
            ->andReturn(gameRowStream([]));

        $this->source->fetch($season, $data);
    });

    test('passes unsupported season type through unchanged before calling the client', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $data = CFBDImportData(['year' => 2023, 'season_type' => 'Custom Season Type', 'week' => 4]);

        $this->client
            ->shouldReceive('fetchGames')
            ->once()
            ->with(['year' => 2023, 'seasonType' => 'Custom Season Type', 'week' => 4])
            ->andReturn(gameRowStream([]));

        $this->source->fetch($season, $data);
    });

    test('passes null for missing options rather than omitting them', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $data = new GameImportData(source: 'cfbd', options: []);

        $this->client
            ->shouldReceive('fetchGames')
            ->once()
            ->with(['year' => null, 'seasonType' => null, 'week' => null])
            ->andReturn(gameRowStream([]));

        $this->source->fetch($season, $data);
    });
});

// ─────────────────────────────────────────────────────────
// fetch – successful mapping
// ─────────────────────────────────────────────────────────

describe('fetch – successful mapping', function () {
    test('returns an empty result when the client returns no games', function () {
        $season = seasonWithSport(Sport::FOOTBALL);

        // no games are returned
        $this->client->allows('fetchGames')->andReturn(gameRowStream([]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toBe([])
            ->and($result->errors)->toBe([]);
    });

    test('maps a raw game to an ImportedGameData DTO', function () {
        $season = seasonWithSport(Sport::FOOTBALL);

        // a sample game with known values is returned by the client
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame()]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toHaveCount(1)
            ->and($result->games[0])->toBeInstanceOf(ImportedGameData::class)
            ->and($result->games[0]->homeTeam)->toBe('Stanford')
            ->and($result->games[0]->homeTeamConference)->toBe('ACC')
            ->and($result->games[0]->awayTeam)->toBe("Hawai'i")
            ->and($result->games[0]->awayTeamConference)->toBe('Mountain West')
            ->and($result->games[0]->homeTeamScore)->toBe(21)
            ->and($result->games[0]->awayTeamScore)->toBe(14)
            ->and($result->games[0]->startTimeTBD)->toBeTrue();
    });

    test('includes a parseable startDateTime string', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame(['startDate' => '2024-09-07T19:30:00.000Z'])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games[0]->startDateTime)->toContain('2024-09-07');
    });

    test('includes a parseable startDateTime time component', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame(['startDate' => '2024-09-07T19:30:00.000Z'])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games[0]->startDateTime)->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
    });

    test('trims surrounding whitespace from team names', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame([
            'homeTeam' => '  Stanford  ',
            'awayTeam' => " Hawai'i ",
        ])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games[0]->homeTeam)->toBe('Stanford')
            ->and($result->games[0]->awayTeam)->toBe("Hawai'i");
    });

    test('defaults home and away scores to zero when points fields are null', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame([
            'homePoints' => null,
            'awayPoints' => null,
        ])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games[0]->homeTeamScore)->toBe(0)
            ->and($result->games[0]->awayTeamScore)->toBe(0);
    });

    test('defaults home and away scores to zero when points fields are absent', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        // Omit homePoints / awayPoints entirely.
        $this->client->allows('fetchGames')->andReturn(gameRowStream([[
            'id' => 401858201,
            'homeTeam' => 'Stanford',
            'awayTeam' => "Hawai'i",
            'startDate' => '2024-09-07T19:30:00.000Z',
        ]]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games[0]->homeTeamScore)->toBe(0)
            ->and($result->games[0]->awayTeamScore)->toBe(0);
    });

    test('maps multiple games from a realistic CFBD response payload', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([
            sampleGame(),
            sampleGame([
                'id' => 401864494,
                'venueId' => 477,
                'venue' => 'Los Angeles Memorial Coliseum',
                'homeId' => 30,
                'homeTeam' => 'USC',
                'homeConference' => 'Big Ten',
                'awayId' => 23,
                'awayTeam' => 'San José State',
                'awayConference' => 'Mountain West',
                'homePoints' => null,
                'awayPoints' => null,
            ]),
        ]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toHaveCount(2)
            ->and($result->errors)->toBe([])
            ->and($result->games[0]->homeTeam)->toBe('Stanford')
            ->and($result->games[0]->awayTeam)->toBe("Hawai'i")
            ->and($result->games[1]->homeTeam)->toBe('USC')
            ->and($result->games[1]->awayTeam)->toBe('San José State')
            ->and($result->games[1]->homeTeamScore)->toBe(0)
            ->and($result->games[1]->awayTeamScore)->toBe(0);
    });

    test('still accepts the legacy snake_case keys', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([[
            'home_team' => 'Alabama',
            'away_team' => 'Georgia',
            'home_conference' => 'SEC',
            'away_conference' => 'SEC',
            'home_points' => 35,
            'away_points' => 17,
            'start_date' => '2024-09-07T19:30:00.000Z',
            'start_time_tbd' => false,
        ]]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toHaveCount(1)
            ->and($result->games[0]->homeTeam)->toBe('Alabama')
            ->and($result->games[0]->homeTeamConference)->toBe('SEC')
            ->and($result->games[0]->awayTeam)->toBe('Georgia')
            ->and($result->games[0]->awayTeamConference)->toBe('SEC')
            ->and($result->games[0]->homeTeamScore)->toBe(35)
            ->and($result->games[0]->awayTeamScore)->toBe(17)
            ->and($result->games[0]->startTimeTBD)->toBeFalse();
    });
});

// ─────────────────────────────────────────────────────────
// fetch – skip logic
// ─────────────────────────────────────────────────────────

describe('fetch – skip logic', function () {
    test('skips a game and records an error when homeTeam is missing', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame(['homeTeam' => null])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toBeEmpty()
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain('required fields were missing from the response');
    });

    test('skips a game and records an error when awayTeam is missing', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame(['awayTeam' => null])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toBeEmpty()
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain('required fields were missing from the response');
    });

    test('skips a game and records an error when startDate is missing', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame(['startDate' => null])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toBeEmpty()
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain('required fields were missing from the response');
    });

    test('skips a game and records an error when startDate cannot be parsed', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([sampleGame(['startDate' => 'not-a-date'])]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toBeEmpty()
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain("start date 'not-a-date' could not be parsed");
    });

    test('includes the game identifier in skip error messages', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([
            sampleGame(),                             // game 1 – valid
            sampleGame(['homeTeam' => null]),         // game 2 – skipped
        ]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->errors[0])->toContain('game 401858201');
    });

    test('processes valid games even when earlier games in the batch are skipped', function () {
        $season = seasonWithSport(Sport::FOOTBALL);
        $this->client->allows('fetchGames')->andReturn(gameRowStream([
            sampleGame(['homeTeam' => null]),     // skipped
            sampleGame(),                          // valid
        ]));

        $result = collectGameStream($this->source->fetch($season, CFBDImportData()));

        expect($result->games)->toHaveCount(1)
            ->and($result->errors)->toHaveCount(1);
    });
});

// ─────────────────────────────────────────────────────────
// fetch – client exceptions
// ─────────────────────────────────────────────────────────

describe('fetch – client exceptions', function () {
    test('propagates a GameImportException thrown by the client', function () {
        $season = seasonWithSport(Sport::FOOTBALL);

        $this->client
            ->allows('fetchGames')
            ->andThrow(new GameImportException('CFBD API credentials are not configured.'));

        $this->source->fetch($season, CFBDImportData());
    })->throws(GameImportException::class, 'CFBD API credentials are not configured.');
});
