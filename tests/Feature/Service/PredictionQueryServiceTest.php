<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Prediction;
use App\Services\PredictionQueryService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

beforeEach(function () {
    $this->service = new PredictionQueryService;
});

describe('getPredictionsForPlayersAndGames', function () {
    test('returns predictions scoped to provided players and games', function () {
        $playerA = Player::factory()->create();
        $playerB = Player::factory()->create();
        $otherPlayer = Player::factory()->create();

        $gameA = Game::factory()->create();
        $gameB = Game::factory()->create();
        $otherGame = Game::factory()->create();

        $includedA = Prediction::factory()->create([
            'player_id' => $playerA->id,
            'game_id' => $gameA->id,
        ]);

        $includedB = Prediction::factory()->create([
            'player_id' => $playerB->id,
            'game_id' => $gameB->id,
        ]);

        // Excluded: player not in input set.
        Prediction::factory()->create([
            'player_id' => $otherPlayer->id,
            'game_id' => $gameA->id,
        ]);

        // Excluded: game not in input set.
        Prediction::factory()->create([
            'player_id' => $playerA->id,
            'game_id' => $otherGame->id,
        ]);

        $players = new EloquentCollection([$playerA, $playerB]);
        $games = collect([$gameA, $gameB]);

        $result = $this->service->getPredictionsForPlayersAndGames($players, $games);

        expect($result)->toHaveCount(2);
        expect($result->pluck('id')->all())->toContain($includedA->id, $includedB->id);
    });

    test('returns empty collection when players are empty', function () {
        $result = $this->service->getPredictionsForPlayersAndGames(new EloquentCollection, collect([Game::factory()->create()]));

        expect($result)->toBeInstanceOf(EloquentCollection::class);
        expect($result)->toBeEmpty();
    });

    test('returns empty collection when games are empty', function () {
        $result = $this->service->getPredictionsForPlayersAndGames(new EloquentCollection([Player::factory()->create()]), collect());

        expect($result)->toBeInstanceOf(EloquentCollection::class);
        expect($result)->toBeEmpty();
    });
});
