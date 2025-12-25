<?php

use App\Models\Player;
use App\Models\Member;
use App\Models\Score;
use App\Models\Game;
use App\Services\PlayerService;
use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedScoreData;

beforeEach(function () {
    $this->service = new PlayerService();
});

describe('create a player for member', function () {
    test('with valid data', function () {
        // create member
        $member = Member::factory()->create();

        // player data
        $data = ValidatedPlayerData::fromArray([
            'player_name' => 'Test Player',
        ]);

        // ensure player does not exist
        $this->assertDatabaseMissing('players', [
            'member_id' => $member->id,
            'player_name' => $data->player_name,
        ]);

        // try to create the player
        $player = $this->service->createForMember($member, $data);

        // verify player exists in database
        $this->assertDatabaseHas('players', [
            'member_id' => $member->id,
            'player_name' => $data->player_name,
        ]);

        expect($player)->toBeInstanceOf(Player::class);
        expect($player->member_id)->toBe($member->id);
        expect($player->player_name)->toBe($data->player_name);
    });
});

describe('update a player', function () {
    test('with valid data', function () {
        // create existing player
        $player = Player::factory()->create([
            'player_name' => 'Old Name',
        ]);

        // new member
        $newMember = Member::factory()->create();

        // data to update to
        $data = ValidatedPlayerData::fromArray([
            'player_name' => 'New Name',
            'member_id' => $newMember->id,
        ]);

        // try to update the player
        $updatedPlayer = $this->service->update($player, $data);

        // verify updated player in database
        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'player_name' => $data->player_name,
            'member_id' => $data->member_id,
        ]);

        // verify returned player is the same instance
        expect($updatedPlayer)->toBe($player);

        // verify updated data
        expect($player->player_name)->toBe($data->player_name);
        expect($player->member_id)->toBe($data->member_id);
    });

    test('does not update when fields are null', function () {
        // create existing player
        $player = Player::factory()->create([
            'player_name' => 'Original Name',
        ]);

        // data with null fields
        $data = ValidatedPlayerData::fromArray([
            'player_name' => 'Original Name', // same as current
            'member_id' => null,
        ]);

        // try to update the player
        $updatedPlayer = $this->service->update($player, $data);

        // verify data unchanged
        expect($updatedPlayer->player_name)->toBe('Original Name');
    });
});

describe('delete a player', function () {
    test('works', function () {
        // create a player
        $player = Player::factory()->create();

        // verify player exists in database
        $this->assertDatabaseHas('players', ['id' => $player->id]);

        // try to delete the player
        $this->service->delete($player);

        // verify player is deleted from database
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    });
});

describe('query players', function () {
    test('returns query builder', function () {
        // try to query players
        $query = $this->service->query([]);

        // verify returns query builder
        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
    });
});

describe('submit a score for player', function () {
    test('with valid data', function () {
        // create player and game
        $player = Player::factory()->create();
        $game = Game::factory()->create();

        // score data
        $data = ValidatedScoreData::fromArray([
            'game_id' => $game->id,
            'home_team_prediction' => 2,
            'away_team_prediction' => 1,
        ]);

        // ensure score does not exist
        $this->assertDatabaseMissing('scores', [
            'player_id' => $player->id,
            'game_id' => $game->id,
        ]);

        // try to submit the score
        $score = $this->service->submitScore($player, $data);

        // verify score exists in database
        $this->assertDatabaseHas('scores', [
            'player_id' => $player->id,
            'game_id' => $data->game_id,
            'home_team_prediction' => $data->home_team_prediction,
            'away_team_prediction' => $data->away_team_prediction,
        ]);

        expect($score)->toBeInstanceOf(Score::class);
        expect($score->player_id)->toBe($player->id);
        expect($score->game_id)->toBe($data->game_id);
        expect($score->home_team_prediction)->toBe($data->home_team_prediction);
        expect($score->away_team_prediction)->toBe($data->away_team_prediction);
    });
});

describe('update a score', function () {
    test('with valid data', function () {
        // create existing score
        $score = Score::factory()->create([
            'home_team_prediction' => 1,
            'away_team_prediction' => 0,
        ]);

        // data to update to
        $data = ValidatedScoreData::fromArray([
            'home_team_prediction' => 3,
            'away_team_prediction' => 2,
        ]);

        // try to update the score
        $updatedScore = $this->service->updateScore($score, $data);

        // verify updated score in database
        $this->assertDatabaseHas('scores', [
            'id' => $score->id,
            'home_team_prediction' => $data->home_team_prediction,
            'away_team_prediction' => $data->away_team_prediction,
        ]);

        // verify returned score is the same instance
        expect($updatedScore)->toBe($score);

        // verify updated data
        expect($score->home_team_prediction)->toBe($data->home_team_prediction);
        expect($score->away_team_prediction)->toBe($data->away_team_prediction);
    });
});

describe('delete a score', function () {
    test('works', function () {
        // create a score
        $score = Score::factory()->create();

        // verify score exists in database
        $this->assertDatabaseHas('scores', ['id' => $score->id]);

        // try to delete the score
        $this->service->deleteScore($score);

        // verify score is deleted from database
        $this->assertDatabaseMissing('scores', ['id' => $score->id]);
    });
});