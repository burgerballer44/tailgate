<?php

use App\Models\Game;
use App\Models\Season;
use App\Models\Team;
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

    test('filters by home_team_id', function () {
        $season = Season::factory()->create();

        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();
        $otherHome = Team::factory()->withSports([$season->sport])->create();

        $matchingGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $otherHome->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $games = $this->service->query([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
        ])->get();

        expect($games)->toHaveCount(1);
        expect($games->first()->id)->toBe($matchingGame->id);
    });

    test('filters by away_team_id', function () {
        $season = Season::factory()->create();

        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();
        $otherAway = Team::factory()->withSports([$season->sport])->create();

        $matchingGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $otherAway->id,
        ]);

        $games = $this->service->query([
            'season_id' => $season->id,
            'away_team_id' => $awayTeam->id,
        ])->get();

        expect($games)->toHaveCount(1);
        expect($games->first()->id)->toBe($matchingGame->id);
    });

    test('filters start_time_tbd when passed as string values', function () {
        $season = Season::factory()->create();

        $homeTeam = Team::factory()->withSports([$season->sport])->create();
        $awayTeam = Team::factory()->withSports([$season->sport])->create();
        $otherHomeTeam = Team::factory()->withSports([$season->sport])->create();
        $otherAwayTeam = Team::factory()->withSports([$season->sport])->create();

        $finalizedGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'start_time_tbd' => false,
        ]);

        $tbdGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $otherHomeTeam->id,
            'away_team_id' => $otherAwayTeam->id,
            'start_time_tbd' => true,
        ]);

        $finalizedGames = $this->service->query([
            'season_id' => $season->id,
            'start_time_tbd' => '0',
        ])->get();

        $tbdGames = $this->service->query([
            'season_id' => $season->id,
            'start_time_tbd' => '1',
        ])->get();

        expect($finalizedGames)->toHaveCount(1);
        expect($finalizedGames->first()->id)->toBe($finalizedGame->id);
        expect($tbdGames)->toHaveCount(1);
        expect($tbdGames->first()->id)->toBe($tbdGame->id);
    });
});

describe('get available teams for season', function () {
    test('returns an array', function () {
        $season = Season::factory()->create();

        $teams = $this->service->getAvailableTeamsForSeason($season);

        expect($teams)->toBeArray();
    });
});
