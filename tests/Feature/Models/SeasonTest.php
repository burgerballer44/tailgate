<?php

use App\Models\Game;
use App\Models\GroupSeasonFollow;
use App\Models\Enums\HtmlEntity;
use App\Models\Season;
use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Symfony\Component\Uid\Ulid;

describe('route binding and identifiers', function () {
    test('uses ulid as route key name', function () {
        expect((new Season)->getRouteKeyName())->toBe('ulid');
    });

    test('generates ulid on create', function () {
        $season = Season::factory()->create();

        expect($season->ulid)->not->toBeNull();
        expect($season->ulid)->toBeInstanceOf(Ulid::class);
        expect((string) $season->ulid)->toHaveLength(26);
    });
});

describe('relationships', function () {
    test('games returns has many relationship', function () {
        $relation = (new Season)->games();

        expect($relation)->toBeInstanceOf(HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(Game::class);
    });

    test('groupSeasonFollows returns has many relationship', function () {
        $relation = (new Season)->groupSeasonFollows();

        expect($relation)->toBeInstanceOf(HasMany::class);
        expect($relation->getRelated())->toBeInstanceOf(GroupSeasonFollow::class);
    });
});

describe('sport_html_entity accessor', function () {
    test('returns html icon for known sport', function () {
        $season = new Season(['sport' => Sport::FOOTBALL->value]);

        $entity = $season->sport_html_entity;

        expect($entity)->toBeInstanceOf(HtmlString::class);
        expect($entity->toHtml())->toBe(Sport::FOOTBALL->htmlEntity()->entity());
    });

    test('returns raw sport value for unknown sport', function () {
        $season = new Season(['sport' => 'Not A Sport']);

        expect($season->sport_html_entity)->toBe('Not A Sport');
    });
});

describe('active_html_entity accessor', function () {
    test('returns check icon for active season', function () {
        $season = new Season(['active' => true]);

        $entity = $season->active_html_entity;

        expect($entity)->toBeInstanceOf(HtmlString::class);
        expect($entity->toHtml())->toBe(HtmlEntity::forBoolean(true)->entity());
    });

    test('returns x icon for inactive season', function () {
        $season = new Season(['active' => false]);

        $entity = $season->active_html_entity;

        expect($entity)->toBeInstanceOf(HtmlString::class);
        expect($entity->toHtml())->toBe(HtmlEntity::forBoolean(false)->entity());
    });
});

describe('filter scope', function () {
    test('filters by q against season name', function () {
        $matching = Season::factory()->create([
            'name' => 'Find Me Season',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ]);

        $nonMatching = Season::factory()->create([
            'name' => 'Something Else',
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ]);

        $results = Season::query()->filter(['q' => 'find me'])->get();

        expect($results->pluck('id')->all())->toContain($matching->id);
        expect($results->pluck('id')->all())->not->toContain($nonMatching->id);
    });

    test('filters by sport and season_type', function () {
        $match = Season::factory()->create([
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ]);

        $wrongSport = Season::factory()->create([
            'sport' => Sport::FOOTBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ]);

        $results = Season::query()->filter([
            'sport' => Sport::BASKETBALL->value,
            'season_type' => SeasonType::REGULAR->value,
        ])->get();

        expect($results->pluck('id')->all())->toContain($match->id);
        expect($results->pluck('id')->all())->not->toContain($wrongSport->id);
    });
});
