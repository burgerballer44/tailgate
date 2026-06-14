<?php

use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedPredictionData;
use App\Models\Game;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use App\Services\PlayerCommandService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = new PlayerCommandService;
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
        expect(Str::isUlid((string) $player->ulid))->toBeTrue();
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

describe('submit prediction', function () {
    test('with valid data', function () {
        // create player and game
        $player = Player::factory()->create();
        $game = Game::factory()->create();

        // prediction data
        $data = [
            'game_id' => $game->id,
            'home_team_prediction' => 2,
            'away_team_prediction' => 1,
        ];

        // submit prediction
        $prediction = $this->service->submitPrediction($player, ValidatedPredictionData::fromArray($data));

        // verify prediction exists in database
        $this->assertDatabaseHas('predictions', [
            'player_id' => $player->id,
            'game_id' => $game->id,
            'home_team_prediction' => 2,
            'away_team_prediction' => 1,
        ]);

        expect($prediction)->toBeInstanceOf(Prediction::class);
        expect($prediction->player_id)->toBe($player->id);
    });
});

describe('update prediction', function () {
    test('with valid data', function () {
        // create existing prediction
        $prediction = Prediction::factory()->create([
            'home_team_prediction' => 1,
            'away_team_prediction' => 0,
        ]);

        // update data
        $data = [
            'home_team_prediction' => 3,
            'away_team_prediction' => 2,
        ];

        // update the prediction
        $updatedPrediction = $this->service->updatePrediction($prediction, ValidatedPredictionData::fromArray($data));

        // verify prediction updated in database
        $this->assertDatabaseHas('predictions', [
            'home_team_prediction' => 3,
            'away_team_prediction' => 2,
        ]);
        expect($updatedPrediction->home_team_prediction)->toBe(3);
        expect($updatedPrediction->away_team_prediction)->toBe(2);
    });
});

describe('delete prediction', function () {
    test('removes prediction from database', function () {
        // create existing prediction
        $prediction = Prediction::factory()->create();

        // delete the prediction
        $this->service->deletePrediction($prediction);

        // verify prediction removed from database
        $this->assertDatabaseMissing('predictions', ['id' => $prediction->id]);
    });
});
