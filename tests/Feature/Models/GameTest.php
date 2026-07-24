<?php

use App\Models\Game;
use App\Models\Enums\HtmlEntity;
use App\Models\Prediction;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Symfony\Component\Uid\Ulid;

describe('route binding and identifiers', function () {
    test('uses ulid as route key name', function () {
        expect((new Game)->getRouteKeyName())->toBe('ulid');
    });

    test('generates ulid on create', function () {
        $game = Game::factory()->create();

        expect($game->ulid)->not->toBeNull();
        expect($game->ulid)->toBeInstanceOf(Ulid::class);
        expect((string) $game->ulid)->toHaveLength(26);
    });
});

describe('casts', function () {
    test('casts score columns to integers and start_time_tbd to boolean', function () {
        $game = Game::factory()->create([
            'home_team_score' => '24',
            'away_team_score' => '17',
            'start_time_tbd' => 1,
        ])->refresh();

        expect($game->home_team_score)->toBeInt()->toBe(24);
        expect($game->away_team_score)->toBeInt()->toBe(17);
        expect($game->start_time_tbd)->toBeBool()->toBeTrue();
    });
});

describe('relationships', function () {
    test('homeTeam returns belongs to relationship', function () {
        $relation = (new Game)->homeTeam();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Team::class);
    });

    test('awayTeam returns belongs to relationship', function () {
        $relation = (new Game)->awayTeam();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Team::class);
    });

    test('season returns belongs to relationship', function () {
        $relation = (new Game)->season();

        expect($relation)->toBeInstanceOf(BelongsTo::class);
        expect($relation->getRelated())->toBeInstanceOf(Season::class);
    });

    test('predictions returns has many relationship', function () {
        $relation = (new Game)->predictions();

        expect($relation)->toBeInstanceOf(HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Prediction::class);
    });
});

describe('eager loading defaults', function () {
    test('eager loads homeTeam and awayTeam by default', function () {
        $game = Game::factory()->create();

        $loadedGame = Game::query()->findOrFail($game->id);

        expect($loadedGame->relationLoaded('homeTeam'))->toBeTrue();
        expect($loadedGame->relationLoaded('awayTeam'))->toBeTrue();
    });
});

describe('start_time_tbd_to_finalized_html_entity accessor', function () {
    test('returns question mark icon when start time is tbd', function () {
        $game = new Game(['start_time_tbd' => true]);

        expect($game->start_time_tbd_to_finalized_html_entity)->toBeInstanceOf(HtmlString::class);
        expect($game->start_time_tbd_to_finalized_html_entity->toHtml())
            ->toBe(HtmlEntity::QUESTION_MARK->entity());
    });

    test('returns check mark icon when start time is finalized', function () {
        $game = new Game(['start_time_tbd' => false]);

        expect($game->start_time_tbd_to_finalized_html_entity)->toBeInstanceOf(HtmlString::class);
        expect($game->start_time_tbd_to_finalized_html_entity->toHtml())
            ->toBe(HtmlEntity::CHECK_MARK->entity());
    });
});
