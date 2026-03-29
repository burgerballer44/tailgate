<?php

use App\DTO\ValidatedGameData;
use App\DTO\ValidatedSeasonData;
use App\Models\Game;
use App\Models\Season;
use App\Models\SeasonType;
use App\Models\Sport;
use App\Models\Team;
use App\Services\Contracts\GameCommandInterface;
use App\Services\SeasonCommandService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->gameCommandService = mock(GameCommandInterface::class);
    $this->service = new SeasonCommandService($this->gameCommandService);
});

describe('create a season', function () {
    test('with valid data', function () {
        // create season data
        $data = [
            'name' => 'Test Season',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::REGULAR->value,
            'season_start' => '2024-01-01',
            'season_end' => '2024-12-31',
            'active' => true,
            'active_date' => '2024-01-01',
            'inactive_date' => '2024-12-31',
        ];

        // ensure season does not exist
        $this->assertDatabaseMissing('seasons', ['name' => $data['name']]);

        // try to create the season
        $season = $this->service->create(ValidatedSeasonData::fromArray($data));

        // verify season exists in database
        $this->assertDatabaseHas('seasons', ['name' => $data['name']]);

        expect($season)->toBeInstanceOf(Season::class);
        expect($season->name)->toBe($data['name']);
        expect($season->sport)->toBe($data['sport']);
        expect($season->season_type)->toBe($data['season_type']);
        expect($season->season_start)->toBe($data['season_start']);
        expect($season->season_end)->toBe($data['season_end']);
        expect($season->active)->toBe($data['active']);
        expect(Str::isUlid((string) $season->ulid))->toBeTrue();
    });
});

describe('update a season', function () {
    test('with valid data', function () {
        // create existing season
        $season = Season::factory()->create([
            'name' => 'Old Name',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR,
            'season_start' => Carbon::parse('2023-01-01'),
            'season_end' => Carbon::parse('2023-12-31'),
        ]);

        // data to update to
        $data = ValidatedSeasonData::fromArray([
            'name' => 'New Name',
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::POST->value,
            'season_start' => '2024-01-01',
            'season_end' => '2024-12-31',
        ]);

        // ensure updated season does not exist
        $this->assertDatabaseMissing('seasons', [
            'name' => $data->name,
        ]);

        // try to update the season
        $updatedSeason = $this->service->update($season, $data);

        // verify updated season exists in database
        $this->assertDatabaseHas('seasons', [
            'name' => $data->name,
        ]);

        // verify returned season is the same instance
        expect($updatedSeason)->toBe($season);

        // verify updated data
        expect($season->name)->toBe($data->name);
        expect($season->sport)->toBe($data->sport->value);
        expect($season->season_type)->toBe($data->season_type->value);
        expect($season->season_start)->toBe((string) $data->season_start);
        expect($season->season_end)->toBe((string) $data->season_end);
    });
});

describe('delete', function () {
    test('deletes a season', function () {
        // create a season
        $season = Season::factory()->create();

        // delete the season
        $this->service->delete($season);

        // verify season is deleted from database
        expect(Season::find($season->id))->toBeNull();
    });
});

describe('add game', function () {
    test('adds a game to a season', function () {
        // create a season
        $season = Season::factory()->create();

        // create teams
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        // game data
        $gameData = ValidatedGameData::fromArray([
            'season_id' => $season->id, // This will be overridden in the service
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 100,
            'away_team_score' => 95,
            'start_date' => '2024-01-01',
            'start_time' => '19:00:00',
        ]);

        // mock the game creation
        $expectedGame = new Game([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 100,
            'away_team_score' => 95,
            'start_date' => '2024-01-01',
            'start_time' => '19:00:00',
        ]);
        $this->gameCommandService->shouldReceive('create')->once()->andReturn($expectedGame);

        // add game to season
        $game = $this->service->addGame($season, $gameData);

        // verify game was created
        expect($game)->toBeInstanceOf(Game::class);
        expect($game->season_id)->toBe($season->id);
        expect($game->home_team_id)->toBe($homeTeam->id);
        expect($game->away_team_id)->toBe($awayTeam->id);
    });
});
