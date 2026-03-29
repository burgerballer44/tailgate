<?php

use App\Services\SeasonQueryService;

beforeEach(function () {
    $this->service = new SeasonQueryService();
});

describe('query seasons', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
        expect($query->getModel())->toBeInstanceOf(\App\Models\Season::class);
    });
});

describe('load with games', function () {
    test('loads season with games and teams', function () {
        // create season with games
        $season = \App\Models\Season::factory()->create();
        $game = \App\Models\Game::factory()->create(['season_id' => $season->id]);

        // load with games
        $loadedSeason = $this->service->loadWithGames($season);

        // verify relationships are loaded
        expect($loadedSeason)->toBe($season);
        expect($loadedSeason->relationLoaded('games'))->toBeTrue();
    });
});