<?php

use App\DTO\ValidatedGameData;
use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Services\GameCommandService;

beforeEach(function () {
    $this->service = new GameCommandService;
});

describe('create a game', function () {
    test('with valid data', function () {
        // create season and teams
        $season = Season::factory()->create();
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        // game data
        $data = [
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 100,
            'away_team_score' => 95,
            'start_date_time' => '2024-01-01 19:00:00',
            'start_time_tbd' => false,
        ];

        // ensure game does not exist
        $this->assertDatabaseMissing('games', ['season_id' => $season->id, 'home_team_id' => $homeTeam->id]);

        // try to create the game
        $game = $this->service->create(ValidatedGameData::fromArray($data));

        // verify game exists in database
        $this->assertDatabaseHas('games', ['season_id' => $season->id, 'home_team_id' => $homeTeam->id]);

        expect($game)->toBeInstanceOf(Game::class);
        expect($game->season_id)->toBe($season->id);
        expect($game->home_team_id)->toBe($homeTeam->id);
        expect($game->away_team_id)->toBe($awayTeam->id);
        expect($game->home_team_score)->toBe(100);
        expect($game->away_team_score)->toBe(95);
        expect(Str::isUlid((string) $game->ulid))->toBeTrue();
    });

    test('forces start_time_tbd when start_date_time is null', function () {
        $season = Season::factory()->create();
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = $this->service->create(ValidatedGameData::fromArray([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 10,
            'away_team_score' => 7,
            'start_date_time' => null,
            'start_time_tbd' => false,
        ]));

        expect($game->start_date_time)->toBeNull();
        expect($game->start_time_tbd)->toBeTrue();
    });

    test('forces start_time_tbd when start_date_time has only a date', function () {
        $season = Season::factory()->create();
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();

        $game = $this->service->create(ValidatedGameData::fromArray([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 10,
            'away_team_score' => 7,
            'start_date_time' => '2024-11-02',
            'start_time_tbd' => false,
        ]));

        expect($game->start_date_time)->toBe('2024-11-02 00:00:00');
        expect($game->start_time_tbd)->toBeTrue();
    });
});

describe('update a game', function () {
    test('with valid data', function () {
        // create existing game
        $game = Game::factory()->create([
            'home_team_score' => 80,
            'away_team_score' => 75,
        ]);

        // data to update to
        $data = ValidatedGameData::fromArray([
            'season_id' => $game->season_id,
            'home_team_id' => $game->home_team_id,
            'away_team_id' => $game->away_team_id,
            'home_team_score' => 90,
            'away_team_score' => 85,
            'start_date_time' => $game->start_date_time,
            'start_time_tbd' => $game->start_time_tbd,
        ]);

        // try to update the game
        $this->service->update($game, $data);

        // verify updated data
        $game->refresh();
        expect($game->home_team_score)->toBe(90);
        expect($game->away_team_score)->toBe(85);
    });
});

describe('delete', function () {
    test('deletes a game', function () {
        // create a game
        $game = Game::factory()->create();

        // delete the game
        $this->service->delete($game);

        // verify game is deleted from database
        expect(Game::find($game->id))->toBeNull();
    });
});
