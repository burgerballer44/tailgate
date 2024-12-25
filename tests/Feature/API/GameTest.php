<?php

use App\Models\Game;
use App\Models\Season;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function() {
    $this->user = actAsAPIUser();
});

test('a game can be added to a season', function () {
    // create a season
    $season = Season::factory()->create();

    // make values for a game
    $gameData = Game::factory()->make()->getAttributes();

    // there should be 0 games in the db
    $this->assertDatabaseCount('games', 0);

    // post the season data
    $this->post("api/v1/seasons/{$season->ulid}/games", $gameData)->assertCreated();

    // there should be 1 games in the db
    $this->assertDatabaseCount('games', 1);
});

test('adding a game returns the game', function () {
    // create a season
    $season = Season::factory()->create();

    // make values for a game
    $gameData = Game::factory()->make()->getAttributes();

    // post the season data
    $this->post("api/v1/seasons/{$season->ulid}/games", $gameData)
        ->assertCreated()
        ->assertJson(['data' => [
            'season_id' => $season->id,
            'home_team_id' => $gameData['home_team_id'],
            'away_team_id' => $gameData['away_team_id'],
            'home_team_score' => 0,
            'away_team_score' => 0,
            'start_date' => $gameData['start_date'],
            'start_time' => $gameData['start_time'],
            ]
        ]);
});

test('the ulid field is populated when adding a game', function () {
    // create a season
    $season = Season::factory()->create();

    // make values for a game
    $gameData = Game::factory()->make()->getAttributes();

    // post the season data
    $this->post("api/v1/seasons/{$season->ulid}/games", $gameData)->assertCreated();

    // get the game we posted
    $game = Game::first();

    expect(Str::isUlid($game->ulid))->toBeTrue();
});

test('the home_team_score field is populated and is 0 when adding a game', function () {
    // create a season
    $season = Season::factory()->create();

    // make values for a game
    $gameData = Game::factory()->make()->getAttributes();

    // post the season data
    $this->post("api/v1/seasons/{$season->ulid}/games", $gameData)->assertCreated();

    // get the game we posted
    $game = Game::first();

    expect($game->home_team_score)->toBe(0);
});

test('the away_team_score field is populated and is 0 when adding a game', function () {
    // create a season
    $season = Season::factory()->create();

    // make values for a game
    $gameData = Game::factory()->make()->getAttributes();

    // post the season data
    $this->post("api/v1/seasons/{$season->ulid}/games", $gameData)->assertCreated();

    // get the game we posted
    $game = Game::first();

    expect($game->away_team_score)->toBe(0);
});

test('a game can be deleted from a season', function () {
    // create a season
    $season = Season::factory()->create();
    // add a game
    $game = Game::factory()->create(['season_id' => $season->id]);

    // there should be 1 game in the db
    $this->assertDatabaseCount('games', 1);

    // delete the game
    $this->delete("api/v1/seasons/{$season->ulid}/games/{$game->ulid}")->assertAccepted();

    // there should be no games in the db
    $this->assertDatabaseCount('games', 0);
});

test('a game cannot be deleted from a season it does not belong to', function () {
    // create a season
    $season = Season::factory()->create();
    // add a game for a different season
    $gameFromDifferentSeason = Game::factory()->create();

    // delete the game
    $this->delete("api/v1/seasons/{$season->ulid}/games/{$gameFromDifferentSeason->ulid}")->assertNotFound();
});

test('a game score can be updated', function() {
    // create a season
    $season = Season::factory()->create();
    // add a game
    $game = Game::factory()->create(['season_id' => $season->id]);

    expect($game->home_team_score)->toBe(0);
    expect($game->away_team_score)->toBe(0);

    $scoreData = [
        'home_team_score' => 10,
        'away_team_score' => 20,
        'start_date'      => '2019-10-01',
        'start_time'      => '12:12'
    ];

    // update the game score
    $this->patch("api/v1/seasons/{$season->ulid}/games/{$game->ulid}", $scoreData)->assertNoContent();

    $game->refresh();

    expect($game->home_team_score)->toBe(10);
    expect($game->away_team_score)->toBe(20);
});

test('a game score can be updated if not part of the season', function() {
    // create a season
    $season = Season::factory()->create();
    // add a game for a different season
    $gameFromDifferentSeason = Game::factory()->create();

    $scoreData = [
        'home_team_score' => 10,
        'away_team_score' => 20,
        'start_date'      => '2019-10-01',
        'start_time'      => '12:12'
    ];

    // update the game score
    $this->patch("api/v1/seasons/{$season->ulid}/games/{$gameFromDifferentSeason->ulid}", $scoreData)->assertNotFound();
});

test('all games are returned when retrieving a season', function () {
    // create a season
    $season = Season::factory()->create();
    // add a game
    $game = Game::factory()->create(['season_id' => $season->id]);

    // get the season
    $this->get("api/v1/seasons/{$season->ulid}")
        ->assertOk()
        ->assertJsonCount(1 , 'data.games')
        ->assertJson(['data' => [
            'games'=> [
                [
                    'season_id' => $season->id,
                    'home_team_id' => $game['home_team_id'],
                    'away_team_id' => $game['away_team_id'],
                    'home_team_score' => 0,
                    'away_team_score' => 0,
                    'start_date' => $game['start_date'],
                    'start_time' => $game['start_time'],
                    'home_team' => [
                        'designation' => $game->homeTeam->designation,
                        'mascot' => $game->homeTeam->mascot,
                    ],
                    'away_team' => [
                        'designation' => $game->awayTeam->designation,
                        'mascot' => $game->awayTeam->mascot,
                    ],
                ]
            ],
            ]
        ]);
});