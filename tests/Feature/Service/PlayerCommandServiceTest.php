<?php

use App\Models\Player;
use App\Models\Member;
use App\Models\Score;
use App\Models\Game;
use Illuminate\Support\Str;
use App\Services\PlayerCommandService;
use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedScoreData;

beforeEach(function () {
    $this->service = new PlayerCommandService();
});

describe('create player for member', function () {
    test('with valid data', function () {
        // create member
        $member = Member::factory()->create();

        // create player data
        $data = [
            'player_name' => 'Test Player',
        ];

        // ensure player does not exist
        $this->assertDatabaseMissing('players', ['player_name' => $data['player_name']]);

        // try to create the player
        $player = $this->service->createForMember($member, ValidatedPlayerData::fromArray($data));

        // verify player exists in database
        $this->assertDatabaseHas('players', ['player_name' => $data['player_name']]);

        expect($player)->toBeInstanceOf(Player::class);
        expect($player->player_name)->toBe($data['player_name']);
        expect($player->member_id)->toBe($member->id);
        expect(Str::isUlid((string)$player->ulid))->toBeTrue();
    });
});

describe('update player', function () {
    test('with valid data', function () {
        // create existing player
        $player = Player::factory()->create([
            'player_name' => 'Old Name',
        ]);

        // update data
        $data = [
            'player_name' => 'New Name',
        ];

        // update the player
        $updatedPlayer = $this->service->update($player, ValidatedPlayerData::fromArray($data));

        // verify player updated in database
        $this->assertDatabaseHas('players', ['player_name' => $data['player_name']]);
        expect($updatedPlayer->player_name)->toBe($data['player_name']);
    });
});

describe('delete player', function () {
    test('removes player from database', function () {
        // create existing player
        $player = Player::factory()->create();

        // delete the player
        $this->service->delete($player);

        // verify player removed from database
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    });
});

describe('submit score', function () {
    test('with valid data', function () {
        // create player and game
        $player = Player::factory()->create();
        $game = Game::factory()->create();

        // score data
        $data = [
            'game_id' => $game->id,
            'home_team_prediction' => 2,
            'away_team_prediction' => 1,
        ];

        // submit score
        $score = $this->service->submitScore($player, ValidatedScoreData::fromArray($data));

        // verify score exists in database
        $this->assertDatabaseHas('scores', [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 2,
            'away_team_prediction' => 1,
        ]);

        expect($score)->toBeInstanceOf(Score::class);
        expect($score->player_id)->toBe($player->id);
    });
});

describe('update score', function () {
    test('with valid data', function () {
        // create existing score
        $score = Score::factory()->create([
            'home_team_prediction' => 1,
            'away_team_prediction' => 0,
        ]);

        // update data
        $data = [
            'home_team_prediction' => 3,
            'away_team_prediction' => 2,
        ];

        // update the score
        $updatedScore = $this->service->updateScore($score, ValidatedScoreData::fromArray($data));

        // verify score updated in database
        $this->assertDatabaseHas('scores', [
            'home_team_prediction' => 3,
            'away_team_prediction' => 2,
        ]);
        expect($updatedScore->home_team_prediction)->toBe(3);
        expect($updatedScore->away_team_prediction)->toBe(2);
    });
});

describe('delete score', function () {
    test('removes score from database', function () {
        // create existing score
        $score = Score::factory()->create();

        // delete the score
        $this->service->deleteScore($score);

        // verify score removed from database
        $this->assertDatabaseMissing('scores', ['id' => $score->id]);
    });
});