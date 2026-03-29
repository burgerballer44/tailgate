<?php

use App\Models\Game;
use App\Models\Season;
use App\Services\GameQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    $this->service = new GameQueryService;
});

describe('query games', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(Builder::class);
        expect($query->getModel())->toBeInstanceOf(Game::class);
    });

    test('filters by season_id', function () {
        $season1 = Season::factory()->create();
        $season2 = Season::factory()->create();

        // Create games for different seasons
        Game::factory()->create(['season_id' => $season1->id]);
        Game::factory()->create(['season_id' => $season2->id]);

        $query = $this->service->query(['season_id' => $season1->id]);
        $games = $query->get();

        expect($games)->toHaveCount(1);
        expect($games->first()->season_id)->toBe($season1->id);
    });
});

describe('get available teams for season', function () {
    test('returns an array', function () {
        $season = Season::factory()->create();

        $teams = $this->service->getAvailableTeamsForSeason($season);

        expect($teams)->toBeArray();
    });
});
