<?php

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;

beforeEach(function () {
    $this->user = signInAdminUser();
});

describe('games index', function () {
    test('page loads', function () {
        // create a season
        $season = Season::factory()->create();

        // visit the index page
        $response = $this->get(route('admin.seasons.games.index', $season));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.games.index');

        // assert data is passed to view
        $response->assertViewHas('season', $season);
        $response->assertViewHas('games');
    });
});

describe('creating a game', function () {
    test('shows create form', function () {
        // create a season
        $season = Season::factory()->create();

        // visit the create page
        $response = $this->get(route('admin.seasons.games.create', $season));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.games.create');

        // assert data is passed to view
        $response->assertViewHas('season', $season);
        $response->assertViewHas('teams');
    });

    test('works with valid data', function () {
        // create a season
        $season = Season::factory()->create();
        // create teams for the season's sport
        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();

        // game data
        $gameData = [
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 0,
            'away_team_score' => 0,
            'start_date' => '2024-10-01',
            'start_time' => '7:00 PM',
        ];

        // there should be 0 games in the db
        $this->assertDatabaseCount('games', 0);

        // post the game data
        $response = $this->post(route('admin.seasons.games.store', $season), $gameData);

        // should redirect to index
        $response->assertRedirect(route('admin.seasons.games.index', $season));

        // there should be 1 game in the db
        $this->assertDatabaseCount('games', 1);

        // verify game was created
        $this->assertDatabaseHas('games', [
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'start_date' => '2024-10-01',
            'start_time' => '7:00 PM',
        ]);
    });

    test('flashes success message on store', function () {
        // create a season
        $season = Season::factory()->create();
        // create teams for the season's sport
        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();

        // game data
        $gameData = [
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 0,
            'away_team_score' => 0,
            'start_date' => '2024-10-01',
            'start_time' => '7:00 PM',
        ];

        // post the game data
        $this->post(route('admin.seasons.games.store', $season), $gameData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Game created successfully!');
    });

    test('fails with same home and away team', function () {
        // create a season
        $season = Season::factory()->create();
        // create a team for the season's sport
        $team = Team::factory()->withSports([$season->sport])->create();

        // game data with same home and away team
        $gameData = [
            'season_id' => $season->id,
            'home_team_id' => $team->id,
            'away_team_id' => $team->id,
            'home_team_score' => 10,
            'away_team_score' => 5,
            'start_date' => '2024-10-01',
            'start_time' => '7:00 PM',
        ];

        // post the game data
        $this->post(route('admin.seasons.games.store', $season), $gameData)
            ->assertSessionHasErrors('away_team_id');
    });
});

describe('viewing a game', function () {
    test('works', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);

        // visit the show page
        $response = $this->get(route('admin.seasons.games.show', [$season, $game]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.games.show');

        // assert data is passed to view
        $response->assertViewHas('season', $season);
        $response->assertViewHas('game', $game);
    });
});

describe('updating a game', function () {
    test('shows edit form', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);

        // visit the edit page
        $response = $this->get(route('admin.seasons.games.edit', [$season, $game]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.games.edit');

        // assert data is passed to view
        $response->assertViewHas('season', $season);
        $response->assertViewHas('game', $game);
        $response->assertViewHas('teams');
    });

    test('updates a game', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);
        // create teams for the season's sport
        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();

        // update data
        $updateData = [
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 10,
            'away_team_score' => 5,
            'start_date' => '2024-10-02',
            'start_time' => '8:00 PM',
        ];

        // patch the game data
        $response = $this->patch(route('admin.seasons.games.update', [$season, $game]), $updateData);

        // should redirect
        $response->assertRedirect(route('admin.seasons.games.index', $season));

        // verify game was updated
        $game->refresh();

        expect($game->home_team_id)->toBe($homeTeam->id);
        expect($game->away_team_id)->toBe($awayTeam->id);
        expect($game->home_team_score)->toBe(10);
        expect($game->away_team_score)->toBe(5);
        expect($game->start_date)->toBe('2024-10-02');
        expect($game->start_time)->toBe('8:00 PM');
    });

    test('flashes success message on update', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);
        // create teams for the season's sport
        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();

        // update data
        $updateData = [
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_team_score' => 5,
            'away_team_score' => 3,
            'start_date' => '2024-10-02',
            'start_time' => '8:00 PM',
        ];

        // patch the game data
        $this->patch(route('admin.seasons.games.update', [$season, $game]), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Game updated successfully!');
    });

    test('fails with same home and away team', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);
        // create a team for the season's sport
        $team = Team::factory()->withSports([$season->sport])->create();

        // update data with same home and away team
        $updateData = [
            'season_id' => $season->id,
            'home_team_id' => $team->id,
            'away_team_id' => $team->id,
            'home_team_score' => 0,
            'away_team_score' => 0,
            'start_date' => '2024-10-02',
            'start_time' => '8:00 PM',
        ];

        // patch the game data
        $this->patch(route('admin.seasons.games.update', [$season, $game]), $updateData)
            ->assertSessionHasErrors('away_team_id');
    });
});

describe('deleting a game', function () {
    test('works', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);

        // there should be 1 game in the db
        $this->assertDatabaseCount('games', 1);

        // delete the game
        $response = $this->delete(route('admin.seasons.games.destroy', [$season, $game]));

        // should redirect to index
        $response->assertRedirect(route('admin.seasons.games.index', $season));

        // there should be 0 games in the db
        $this->assertDatabaseCount('games', 0);

        // verify game was deleted
        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    });

    test('flashes success message on delete', function () {
        // create a season
        $season = Season::factory()->create();
        // create a game for the season
        $game = Game::factory()->create(['season_id' => $season->id]);

        // delete the game
        $this->delete(route('admin.seasons.games.destroy', [$season, $game]))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('Game deleted successfully!');
    });
});