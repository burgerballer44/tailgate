<?php

use App\DTO\GameImportData;
use App\DTO\ImportedGameData;
use App\DTO\ImportFetchStream;
use App\Exceptions\GameImportException;
use App\Models\Game;
use App\Models\Season;
use App\Models\Sport;
use App\Models\Team;
use App\Services\Contracts\GameCommandInterface;
use App\Services\Contracts\GameImportSourceInterface;
use App\Services\Contracts\SeasonCommandInterface;
use App\Services\GameImportManager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─────────────────────────────────────────────────────────
// Shared setup
// ─────────────────────────────────────────────────────────

beforeEach(function () {
    $this->seasonCommandService = Mockery::mock(SeasonCommandInterface::class);
    $this->gameCommandService = Mockery::mock(GameCommandInterface::class);
});

/**
 * Build a mock GameImportSourceInterface with sensible defaults.
 * Individual tests can override allows() on the returned mock.
 */
function fakeSource(string $key = 'test', string $label = 'Test Source'): GameImportSourceInterface
{
    $source = Mockery::mock(GameImportSourceInterface::class);
    $source->allows('key')->andReturn($key);
    $source->allows('label')->andReturn($label);
    $source->allows('type')->andReturn('api');
    $source->allows('description')->andReturn('A test import source.');

    return $source;
}

/**
 * Minimal GameImportData for tests that do not care about options.
 */
function fakeImportData(string $source = 'test', array $options = []): GameImportData
{
    return new GameImportData(
        source: $source,
        options: $options ?: ['year' => 2024, 'season_type' => 'regular'],
    );
}

function importedGame(
    string $homeTeam,
    string $awayTeam,
    int $homeTeamScore,
    int $awayTeamScore,
    string $startDate,
    string $startTime,
    bool $startTimeTBD = false,
): ImportedGameData {
    $startDateTime = CarbonImmutable::parse("{$startDate} {$startTime}")->toDateTimeString();

    return new ImportedGameData(
        homeTeam: $homeTeam,
        homeTeamConference: '',
        awayTeam: $awayTeam,
        awayTeamConference: '',
        homeTeamScore: $homeTeamScore,
        awayTeamScore: $awayTeamScore,
        startDateTime: $startDateTime,
        startTimeTBD: $startTimeTBD,
    );
}

// ─────────────────────────────────────────────────────────
// availableSources
// ─────────────────────────────────────────────────────────

describe('availableSources', function () {
    test('returns an empty array when no sources are registered', function () {
        $manager = new GameImportManager(
            seasonCommandService: $this->seasonCommandService,
            gameCommandService: $this->gameCommandService,
            sources: [],
        );

        expect($manager->availableSources())->toBe([]);
    });

    test('returns structured metadata for each registered source', function () {
        $source = fakeSource(key: 'cfbd', label: 'CFBD API');

        $manager = new GameImportManager(
            seasonCommandService: $this->seasonCommandService,
            gameCommandService: $this->gameCommandService,
            sources: [$source],
        );

        $result = $manager->availableSources();

        expect($result)->toHaveCount(1)
            ->and($result[0])->toBe([
                'value' => 'cfbd',
                'label' => 'CFBD API',
                'description' => 'A test import source.',
                'type' => 'api',
            ]);
    });

    test('includes all registered sources in the returned array', function () {
        $manager = new GameImportManager(
            seasonCommandService: $this->seasonCommandService,
            gameCommandService: $this->gameCommandService,
            sources: [fakeSource('a', 'Source A'), fakeSource('b', 'Source B')],
        );

        expect($manager->availableSources())->toHaveCount(2);
    });
});

// ─────────────────────────────────────────────────────────
// import – source resolution
// ─────────────────────────────────────────────────────────

describe('import – source resolution', function () {
    test('throws GameImportException when the requested source key is not registered', function () {
        $manager = new GameImportManager(
            seasonCommandService: $this->seasonCommandService,
            gameCommandService: $this->gameCommandService,
            sources: [],
        );

        $season = Season::factory()->create();

        $manager->import($season, fakeImportData('unknown'));
    })->throws(GameImportException::class, 'Selected import source is not available.');

    test('uses the source whose key matches the import data', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);

        $matchingSource = fakeSource('cfbd');
        $matchingSource->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: []));

        $manager = new GameImportManager(
            seasonCommandService: $this->seasonCommandService,
            gameCommandService: $this->gameCommandService,
            sources: [fakeSource('other'), $matchingSource],
        );

        // Should not throw – the correct source was found.
        $result = $manager->import($season, fakeImportData('cfbd'));

        expect($result->source)->toBe('cfbd');
    });
});

// ─────────────────────────────────────────────────────────
// import – successful game creation
// ─────────────────────────────────────────────────────────

describe('import – successful game creation', function () {
    test('imports a game when both teams are resolved', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Georgia', 24, 17, '2024-09-07', '3:30 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(1)
            ->and($result->errors)->toBe([]);
    });

    test('returns the source label in the result', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource(key: 'test', label: 'My Source');
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Georgia', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->allows('addGame');

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->sourceLabel)->toBe('My Source');
    });

    test('returns zero imported count and no errors when source returns no games', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: []));

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->errors)->toBe([]);
    });
});

// ─────────────────────────────────────────────────────────
// import – team resolution
// ─────────────────────────────────────────────────────────

describe('import – team resolution', function () {
    test('resolves a team by its organization name', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Georgia', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(1);
    });

    test('resolves a team by its designation', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create([
            'organization' => 'University of Alabama',
            'designation' => 'Alabama',
        ]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            // The source supplies the designation value, not the full organization.
            importedGame('Alabama', 'Georgia', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(1);
    });

    test('resolves a team by the combined organization and designation string', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create([
            'organization' => 'Alabama',
            'designation' => 'Crimson Tide',
        ]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create([
            'organization' => 'Georgia',
            'designation' => 'Bulldogs',
        ]);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama Crimson Tide', 'Georgia Bulldogs', 21, 14, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(1);
    });

    test('resolves team names case-insensitively', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('ALABAMA', 'georgia', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(1);
    });

    test('does not resolve teams that belong to a different sport', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);

        // Basketball teams — should never match a football season.
        Team::factory()->withSports([Sport::BASKETBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::BASKETBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Georgia', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldNotReceive('addGame');

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->errors)->toHaveCount(1);
    });
});

// ─────────────────────────────────────────────────────────
// import – skip logic
// ─────────────────────────────────────────────────────────

describe('import – skip logic', function () {
    test('skips a game and records an error when the home team cannot be resolved', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Unknown Team', 'Georgia', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldNotReceive('addGame');

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain("home team 'Unknown Team' was not found");
    });

    test('skips a game and records an error when the away team cannot be resolved', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Unknown Team', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldNotReceive('addGame');

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain("away team 'Unknown Team' was not found");
    });

    test('skips a game and records an error when home and away resolve to the same team', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Alabama', 0, 0, '2024-09-07', '7:00 PM'),
        ]));

        $this->seasonCommandService->shouldNotReceive('addGame');

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain('home and away teams resolved to the same team');
    });

    test('updates an existing game when a game with the same home and away teams already exists in the season', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        $homeTeam = Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        $awayTeam = Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        // Pre-seed an existing game with stale data.
        $existingGame = $season->games()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 0,
            'away_team_score' => 0,
            'start_date_time' => '2024-09-07 00:00:00',
            'start_time_tbd' => true,
        ]);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            // same matchup but with updated scores, start time, and TBD flag
            importedGame('Alabama', 'Georgia', 24, 17, '2024-09-07', '3:30 PM'),
        ]));

        $this->seasonCommandService->shouldNotReceive('addGame');
        $this->gameCommandService->shouldReceive('update')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->updatedCount)->toBe(1)
            ->and($result->errors)->toBe([]);
    });

    test('skips and records an error when the same home/away teams appear twice in the same import batch', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            importedGame('Alabama', 'Georgia', 24, 17, '2024-09-07', '3:30 PM'),
            // duplicate matchup in the same batch
            importedGame('Alabama', 'Georgia', 24, 17, '2024-09-07', '3:30 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();
        $this->gameCommandService->shouldNotReceive('update');

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(1)
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain('already been processed in this import batch');
    });

    test('numbers skipped games starting at 1 relative to their position in the response', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(items: [
            // Game 1 skipped – home team unknown.
            importedGame('Unknown', 'Georgia', 0, 0, '2024-09-07', '7:00 PM'),
            // Game 2 valid.
            importedGame('Alabama', 'Georgia', 0, 0, '2024-09-14', '3:30 PM'),
        ]));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->errors[0])->toContain('game 1')
            ->and($result->importedCount)->toBe(1);
    });
});

// ─────────────────────────────────────────────────────────
// import – error propagation
// ─────────────────────────────────────────────────────────

describe('import – error propagation', function () {
    test('includes fetch-level errors from the source in the result', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [],
            errors: ['Skipped CFBD game 1: required fields were missing from the response.'],
        ));

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        expect($result->importedCount)->toBe(0)
            ->and($result->errors)->toHaveCount(1)
            ->and($result->errors[0])->toContain('required fields were missing');
    });

    test('accumulates fetch errors and skip errors together in the result', function () {
        $season = Season::factory()->create(['sport' => Sport::FOOTBALL->value]);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Alabama']);
        Team::factory()->withSports([Sport::FOOTBALL->value])->create(['organization' => 'Georgia']);

        $source = fakeSource();
        $source->allows('fetch')->andReturn(ImportFetchStream::fromArray(
            items: [
                importedGame('Alabama', 'Georgia', 21, 14, '2024-09-07', '7:00 PM'),
                importedGame('Unknown', 'Georgia', 0, 0, '2024-09-14', '3:30 PM'),
            ],
            errors: ['Skipped CFBD game 0: missing start date.'],
        ));

        $this->seasonCommandService->shouldReceive('addGame')->once();

        $result = (new GameImportManager($this->seasonCommandService, $this->gameCommandService, [$source]))
            ->import($season, fakeImportData());

        // 1 fetch-level error + 1 skip error = 2 total errors, 1 successfully imported.
        expect($result->importedCount)->toBe(1)
            ->and($result->errors)->toHaveCount(2);
    });
});
