<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Prediction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\Ulid;

describe('route binding and identifiers', function () {
    test('uses ulid as route key name', function () {
        expect((new Prediction)->getRouteKeyName())->toBe('ulid');
    });

    test('generates ulid on create', function () {
        $prediction = Prediction::factory()->create();

        expect($prediction->ulid)->not->toBeNull();
        expect($prediction->ulid)->toBeInstanceOf(Ulid::class);
        expect((string) $prediction->ulid)->toHaveLength(26);
    });
});

describe('casts', function () {
    test('casts score predictions to integers', function () {
        $prediction = Prediction::factory()->create([
            'home_team_prediction' => '21',
            'away_team_prediction' => '14',
        ])->refresh();

        expect($prediction->home_team_prediction)->toBeInt()->toBe(21);
        expect($prediction->away_team_prediction)->toBeInt()->toBe(14);
    });
});

describe('relationships', function () {
    test('game returns belongs to relationship', function () {
        $relation = (new Prediction)->game();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Game::class);
    });

    test('player returns belongs to relationship', function () {
        $relation = (new Prediction)->player();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Player::class);
    });
});
