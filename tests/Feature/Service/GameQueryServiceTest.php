<?php

use App\Models\Game;
use App\Models\Group;
use App\Models\Follow;
use App\Models\Season;
use App\Models\Sport;
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

describe('get upcoming games for group', function () {
    test('returns only upcoming games for followed teams', function () {
        $group = Group::factory()->create();

        $season = Season::factory()->active()->create([
            'sport' => Sport::FOOTBALL->value,
        ]);

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $otherHome = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $otherAway = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $includedGame = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $otherHome->id,
            'away_team_id' => $otherAway->id,
            'start_date_time' => now()->addDays(2)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->subDay()->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $games = $this->service->getUpcomingGamesForGroup($group);

        expect($games)->toHaveCount(1);
        expect($games->first()->id)->toBe($includedGame->id);
    });

    test('respects follow sport scope when selecting games', function () {
        $group = Group::factory()->create();

        $team = Team::factory()->withSports([Sport::FOOTBALL, Sport::BASKETBALL])->create();
        $footballOpponent = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $basketballOpponent = Team::factory()->withSports([Sport::BASKETBALL])->create();

        $footballSeason = Season::factory()->active()->create([
            'sport' => Sport::FOOTBALL->value,
        ]);

        $basketballSeason = Season::factory()->active()->create([
            'sport' => Sport::BASKETBALL->value,
        ]);

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $team->id,
            'sport' => Sport::FOOTBALL,
        ]);

        $footballGame = Game::factory()->create([
            'season_id' => $footballSeason->id,
            'home_team_id' => $team->id,
            'away_team_id' => $footballOpponent->id,
            'start_date_time' => now()->addDays(3)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        Game::factory()->create([
            'season_id' => $basketballSeason->id,
            'home_team_id' => $team->id,
            'away_team_id' => $basketballOpponent->id,
            'start_date_time' => now()->addDays(3)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $games = $this->service->getUpcomingGamesForGroup($group);

        expect($games)->toHaveCount(1);
        expect($games->first()->id)->toBe($footballGame->id);
        expect($games->first()->season->sport)->toBe(Sport::FOOTBALL->value);
    });

    test('filters upcoming group games to an inclusive dashboard window end', function () {
        // Arrange: create follow-scoped upcoming games around a 2-week window.
        $group = Group::factory()->create();

        $season = Season::factory()->active()->create([
            'sport' => Sport::FOOTBALL->value,
        ]);

        $followedTeam = Team::factory()->withSports([Sport::FOOTBALL])->create();
        $opponent = Team::factory()->withSports([Sport::FOOTBALL])->create();

        Follow::factory()->create([
            'group_id' => $group->id,
            'team_id' => $followedTeam->id,
            'sport' => null,
        ]);

        $gameInsideWindow = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(10)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        $gameOutsideWindow = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $followedTeam->id,
            'away_team_id' => $opponent->id,
            'start_date_time' => now()->addDays(18)->toDateTimeString(),
            'start_time_tbd' => false,
        ]);

        // Act: load games within the 2-week dashboard window.
        $games = $this->service->getUpcomingGamesForGroupWithinWindow($group, now()->addWeeks(2));

        // Assert: only the in-window game remains.
        expect($games)->toHaveCount(1);
        expect($games->first()->id)->toBe($gameInsideWindow->id);
        expect($games->pluck('id'))->not->toContain($gameOutsideWindow->id);
    });
});
