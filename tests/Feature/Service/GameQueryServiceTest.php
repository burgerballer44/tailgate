<?php

use App\Models\Season;
use App\Models\Team;
use App\Models\Sport;
use App\Services\GameQueryService;

beforeEach(function () {
    $this->service = new GameQueryService();
});

describe('query games', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
        expect($query->getModel())->toBeInstanceOf(\App\Models\Game::class);
    });

    test('filters by season_id', function () {
        $season1 = Season::factory()->create();
        $season2 = Season::factory()->create();

        // Create games for different seasons
        \App\Models\Game::factory()->create(['season_id' => $season1->id]);
        \App\Models\Game::factory()->create(['season_id' => $season2->id]);

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