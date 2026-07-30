<?php

use App\DTO\PredictionPolicyEvaluationResult;
use App\DTO\PredictionPolicyViolation;
use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedPredictionData;
use App\Exceptions\PredictionPolicyViolationException;
use App\Models\Enums\PredictionPolicyScope;
use App\Models\Game;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use App\Models\Season;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\PlayerCommandService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = new PlayerCommandService(
        app(PredictionPolicyEvaluatorInterface::class),
    );
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
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

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

    test('throws a prediction policy violation exception when the evaluator reports violations', function () {
        $violation = new PredictionPolicyViolation(
            key: 'prediction-lock-time',
            label: 'Prediction lock time',
            description: 'Predictions cannot be submitted or updated after the scheduled game start time.',
            scope: PredictionPolicyScope::APP,
        );
        $result = new PredictionPolicyEvaluationResult([$violation]);
        $evaluator = Mockery::mock(PredictionPolicyEvaluatorInterface::class);
        $evaluator->shouldReceive('evaluate')->once()->andReturn($result);

        $service = new PlayerCommandService($evaluator);
        $player = Player::factory()->create();
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);

        $data = [
            'game_id' => $game->id,
            'home_team_prediction' => 2,
            'away_team_prediction' => 1,
        ];

        try {
            $service->submitPrediction($player, ValidatedPredictionData::fromArray($data));

            $this->fail('Expected a PredictionPolicyViolationException to be thrown.');
        } catch (PredictionPolicyViolationException $exception) {
            expect($exception->getMessage())->toBe($result->summary());
            expect($exception->result())->toBe($result);
        }
    });
});

describe('update prediction', function () {
    test('with valid data', function () {
        // create existing prediction
        $season = Season::factory()->active()->create();
        $prediction = Prediction::factory()->create([
            'game_id' => Game::factory()->create([
                'season_id' => $season->id,
                'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
                'start_time_tbd' => false,
            ])->id,
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

    test('throws a prediction policy violation exception when the evaluator reports violations', function () {
        $violation = new PredictionPolicyViolation(
            key: 'prediction-lock-time',
            label: 'Prediction lock time',
            description: 'Predictions cannot be submitted or updated after the scheduled game start time.',
            scope: PredictionPolicyScope::APP,
        );
        $result = new PredictionPolicyEvaluationResult([$violation]);
        $evaluator = Mockery::mock(PredictionPolicyEvaluatorInterface::class);
        $evaluator->shouldReceive('evaluate')->once()->andReturn($result);

        $service = new PlayerCommandService($evaluator);
        $season = Season::factory()->active()->create();
        $game = Game::factory()->create([
            'season_id' => $season->id,
            'start_date_time' => now()->addDay()->format('Y-m-d H:i:s'),
            'start_time_tbd' => false,
        ]);
        $prediction = Prediction::factory()->create([
            'game_id' => $game->id,
            'home_team_prediction' => 1,
            'away_team_prediction' => 0,
        ]);

        $data = [
            'home_team_prediction' => 3,
            'away_team_prediction' => 2,
        ];

        try {
            $service->updatePrediction($prediction, ValidatedPredictionData::fromArray($data));

            $this->fail('Expected a PredictionPolicyViolationException to be thrown.');
        } catch (PredictionPolicyViolationException $exception) {
            expect($exception->getMessage())->toBe($result->summary());
            expect($exception->result())->toBe($result);
        }
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
