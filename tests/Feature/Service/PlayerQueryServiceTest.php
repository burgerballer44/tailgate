<?php

use App\Models\Player;
use App\Services\PlayerQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    $this->service = new PlayerQueryService;
});

describe('query players', function () {
    test('returns query builder', function () {
        $result = $this->service->query([]);

        expect($result)->toBeInstanceOf(Builder::class);
        expect($result->getModel())->toBeInstanceOf(Player::class);
    });

    test('filters players by query parameters', function () {
        // create test players
        $player1 = Player::factory()->create(['player_name' => 'John Doe']);
        $player2 = Player::factory()->create(['player_name' => 'Jane Smith']);

        // query with filter
        $result = $this->service->query(['q' => 'John']);

        $players = $result->get();
        expect($players)->toHaveCount(1);
        expect($players->first()->id)->toBe($player1->id);
    });
});
