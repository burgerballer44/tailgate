<?php

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
use App\Services\GameService;
use App\DTO\ValidatedGameData;

beforeEach(function () {
    $this->service = new GameService();
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
            'home_team_score' => 2,
            'away_team_score' => 1,
            'start_date' => '2024-01-01',
            'start_time' => '15:00',
        ];

        // ensure game does not exist
        $this->assertDatabaseMissing('games', [
            'season_id' => $data['season_id'],
            'home_team_id' => $data['home_team_id'],
            'away_team_id' => $data['away_team_id'],
        ]);

        // try to create the game
        $game = $this->service->create(ValidatedGameData::fromArray($data));

        // verify game exists in database
        $this->assertDatabaseHas('games', $data);

        expect($game)->toBeInstanceOf(Game::class);
        expect($game->season_id)->toBe($data['season_id']);
        expect($game->home_team_id)->toBe($data['home_team_id']);
        expect($game->away_team_id)->toBe($data['away_team_id']);
        expect($game->home_team_score)->toBe($data['home_team_score']);
        expect($game->away_team_score)->toBe($data['away_team_score']);
        expect($game->start_date)->toBe($data['start_date']);
        expect($game->start_time)->toBe($data['start_time']);
    });
});

describe('update a game', function () {
    test('with valid data', function () {
        // create existing game
        $game = Game::factory()->create([
            'home_team_score' => 0,
            'away_team_score' => 0,
        ]);

        // new season and teams
        $newSeason = Season::factory()->create();
        $newHomeTeam = Team::factory()->create();
        $newAwayTeam = Team::factory()->create();

        // data to update to
        $data = ValidatedGameData::fromArray([
            'season_id' => $newSeason->id,
            'home_team_id' => $newHomeTeam->id,
            'away_team_id' => $newAwayTeam->id,
            'home_team_score' => 3,
            'away_team_score' => 2,
            'start_date' => '2024-02-01',
            'start_time' => '16:00',
        ]);

        // try to update the game
        $this->service->update($game, $data);

        // verify updated game in database
        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'season_id' => $data->season_id,
            'home_team_id' => $data->home_team_id,
            'away_team_id' => $data->away_team_id,
            'home_team_score' => $data->home_team_score,
            'away_team_score' => $data->away_team_score,
            'start_date' => $data->start_date,
            'start_time' => $data->start_time,
        ]);

        // verify updated data
        $game->refresh();
        expect($game->season_id)->toBe($data->season_id);
        expect($game->home_team_id)->toBe($data->home_team_id);
        expect($game->away_team_id)->toBe($data->away_team_id);
        expect($game->home_team_score)->toBe($data->home_team_score);
        expect($game->away_team_score)->toBe($data->away_team_score);
        expect((string)$game->start_date)->toBe((string)$data->start_date);
        expect((string)$game->start_time)->toBe((string)$data->start_time);
    });
});

describe('delete a game', function () {
    test('works', function () {
        // create a game
        $game = Game::factory()->create();

        // verify game exists in database
        $this->assertDatabaseHas('games', ['id' => $game->id]);

        // try to delete the game
        $this->service->delete($game);

        // verify game is deleted from database
        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    });
});

describe('query games', function () {
    test('returns query builder', function () {
        // try to query games
        $query = $this->service->query([]);

        // verify returns query builder
        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
    });

    test('filters by season_id', function () {
        // create seasons and games
        $season1 = Season::factory()->create();
        $season2 = Season::factory()->create();

        Game::factory()->create(['season_id' => $season1->id]);
        Game::factory()->create(['season_id' => $season1->id]);
        Game::factory()->create(['season_id' => $season2->id]);

        // query for season1's games
        $query = $this->service->query(['season_id' => $season1->id]);
        $results = $query->get();

        // verify only season1's games are returned
        expect($results->count())->toBe(2);
        $results->each(function ($game) use ($season1) {
            expect($game->season_id)->toBe($season1->id);
        });
    });
});

describe('get available teams for season', function () {
    test('returns teams with matching sport', function () {
        // create season with sport
        $season = Season::factory()->create(['sport' => 'Football']);

        // create teams with sports
        $team1 = Team::factory()->withoutSports()->create();
        $team1->sports()->create(['sport' => 'Football']);
        $team2 = Team::factory()->withoutSports()->create();
        $team2->sports()->create(['sport' => 'Football']);
        $team3 = Team::factory()->withoutSports()->create();
        $team3->sports()->create(['sport' => 'Basketball']); // different sport

        // get available teams
        $availableTeams = $this->service->getAvailableTeamsForSeason($season);

        // verify only football teams are returned
        expect(count($availableTeams))->toBe(2);
        expect(isset($availableTeams[$team1->id]))->toBeTrue();
        expect(isset($availableTeams[$team2->id]))->toBeTrue();
        expect(isset($availableTeams[$team3->id]))->toBeFalse();
    });
});