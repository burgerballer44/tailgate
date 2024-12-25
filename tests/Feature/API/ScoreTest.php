<?php

use App\Models\Follow;
use App\Models\Game;
use App\Models\Group;
use App\Models\Player;
use App\Models\Score;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function() {
    $this->user = actAsAPIUser();

    // CREATE THE CONDITIONS FOR A SCORE TO BE SUBMITTED

    // create two team
    $this->homeTeam = Team::factory()->create();
    $this->awayTeam = Team::factory()->create();

    // create a season
    $this->season = Season::factory()->create();

    // create a game
    $this->game = Game::factory()->create(['season_id' => $this->season->id]);

    // create a group
    $this->group = Group::factory()->create();

    // follow the home team
    $this->follow = Follow::factory()->create([
        'group_id' => $this->group->id,
        'team_id' => $this->homeTeam->id,
        'season_id' => $this->season->id,
    ]);

    // add a player to the owner
    $this->player = Player::factory()->create(['member_id' => $this->group->ownerMember->id]);
});

test('a player can submit a score', function () {    
    $scoreData = [
        'player_id' => $this->player->id,
        'game_id' => $this->game->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ];

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);

    // try to submit a score
    $this->post("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score", $scoreData)
        ->assertCreated();

    // there should be 1 scores in the db
    $this->assertDatabaseCount('scores', 1);
});

test('submitting a score populates the ulid field', function () {    
    $scoreData = [
        'player_id' => $this->player->id,
        'game_id' => $this->game->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ];

    // try to submit a score
    $this->post("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score", $scoreData)
        ->assertCreated();

    // get the score we posted
    $score = Score::first();

    expect(Str::isUlid($score->ulid))->toBeTrue();
});

test('a player cannot submit another score for a game they already submitted for', function () {
    $scoreData = [
        'player_id' => $this->player->id,
        'game_id' => $this->game->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ];

    // create a score
    $score = Score::factory()->create($scoreData);

    // there should be 1 scores in the db
    $this->assertDatabaseCount('scores', 1);

    // try to submit a score with the same data
    $this->post("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score", $scoreData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'game_id' => ['A score has already been submitted for this game by this player.']
        ]]);


    // there should be 1 scores in the db
    $this->assertDatabaseCount('scores', 1);
});

test('a player cannot submit a score for a game that is not in their followed season', function () {    
    // create a game from a different season
    $gameFromDifferentSeason = Game::factory()->create();

    $scoreData = [
        'player_id' => $this->player->id,
        'game_id' => $gameFromDifferentSeason->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ];

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);

    // try to submit a score
    $this->post("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score", $scoreData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'game_id' => ['Cannot submit a score for a game in a season you are not following.']
        ]]);

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);
});

test('a player cannot submit a score for a game with a date and time in the past', function () {    
    // create a game that is in the past
    $gameAlreadyStarted = Game::factory()->create([
        'season_id' => $this->season->id,
        'start_date' => '2019-10-01',
        'start_time' => '12:12',
    ]);

    $scoreData = [
        'group_id' => $this->group->id,
        'player_id' => $this->player->id,
        'game_id' => $gameAlreadyStarted->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ];

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);

    // try to submit a score
    $this->post("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score", $scoreData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'game_id' => ['The start of the game has passed.']
        ]]);

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);
});

test('a player cannot submit a score for a game with a date in the past and with a TBA start time', function () {    
    // create a game that is in the past
    $gameAlreadyStarted = Game::factory()->create([
        'season_id' => $this->season->id,
        'start_date' => '2019-10-01',
        'start_time' => 'TBA',
    ]);

    $scoreData = [
        'group_id' => $this->group->id,
        'player_id' => $this->player->id,
        'game_id' => $gameAlreadyStarted->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ];

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);

    // try to submit a score
    $this->post("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score", $scoreData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'game_id' => ['The start of the game has passed.']
        ]]);

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);
});

test('a player can update their score', function () {
    // create a score
    $score = Score::factory()->create([
        'player_id' => $this->player->id,
        'game_id' => $this->game->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ]);

    $scoreData = [
        'score_id' => $score->id,
        'home_team_prediction' => 1234,
        'away_team_prediction' => 1111,
    ];

    // try to update the score
    $this->patch("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score/{$score->ulid}", $scoreData)
        ->assertNoContent();

    $score = $score->refresh();

    expect($score->home_team_prediction)->toBe(1234);
    expect($score->away_team_prediction)->toBe(1111);
});

test('a player cannot update a score for a game with a date and time in the past', function () {
    // create a game that is in the past
    $gameAlreadyStarted = Game::factory()->create([
        'season_id' => $this->season->id,
        'start_date' => '2019-10-01',
        'start_time' => '12:12',
    ]);

    // create a score
    $score = Score::factory()->create([
        'player_id' => $this->player->id,
        'game_id' => $gameAlreadyStarted->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ]);

    $scoreData = [
        'score_id' => $score->id,
        'home_team_prediction' => 1234,
        'away_team_prediction' => 1111,
    ];

    // try to update the score
    $this->patch("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score/{$score->ulid}", $scoreData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'score_id' => ['The start of the game has passed.']
        ]]);
});

test('a player cannot update a score for a game with a date in the past and with a TBA start time', function () {
    // create a game that is in the past
    $gameAlreadyStarted = Game::factory()->create([
        'season_id' => $this->season->id,
        'start_date' => '2019-10-01',
        'start_time' => 'TBA',
    ]);

    // create a score
    $score = Score::factory()->create([
        'player_id' => $this->player->id,
        'game_id' => $gameAlreadyStarted->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ]);

    $scoreData = [
        'score_id' => $score->id,
        'home_team_prediction' => 1234,
        'away_team_prediction' => 1111,
    ];

    // try to update the score
    $this->patch("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score/{$score->ulid}", $scoreData)
        ->assertUnprocessable()
        ->assertJson(['data' => [
            'score_id' => ['The start of the game has passed.']
        ]]);
});

test('a player can delete their score', function () {
    // create a score
    $score = Score::factory()->create([
        'player_id' => $this->player->id,
        'game_id' => $this->game->id,
        'home_team_prediction' => 20,
        'away_team_prediction' => 10,
    ]);

    // there should be 1 scores in the db
    $this->assertDatabaseCount('scores', 1);

    // try to delete the score
    $this->delete("api/v1/groups/{$this->group->ulid}/members/{$this->group->ownerMember->ulid}/player/{$this->player->ulid}/score/{$score->ulid}")
        ->assertAccepted();

    // there should be 0 scores in the db
    $this->assertDatabaseCount('scores', 0);
});