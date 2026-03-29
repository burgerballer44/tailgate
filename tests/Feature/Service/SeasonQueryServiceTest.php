<?php

use App\Models\Game;
use App\Models\Season;
use App\Services\SeasonQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    $this->service = new SeasonQueryService;
});

describe('query seasons', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(Builder::class);
        expect($query->getModel())->toBeInstanceOf(Season::class);
    });
});

describe('load with games', function () {
    test('loads season with games and teams', function () {
        // create season with games
        $season = Season::factory()->create();
        $game = Game::factory()->create(['season_id' => $season->id]);

        // load with games
        $loadedSeason = $this->service->loadWithGames($season);

        // verify relationships are loaded
        expect($loadedSeason)->toBe($season);
        expect($loadedSeason->relationLoaded('games'))->toBeTrue();
    });
});
