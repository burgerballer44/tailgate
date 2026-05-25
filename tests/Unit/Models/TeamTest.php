<?php

use App\Models\HtmlEntity;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamSport;
use Illuminate\Support\HtmlString;

describe('sports_html_entities', function () {
    test('returns an empty html string when team has no sports', function () {
        $team = new Team;
        $team->setRelation('sports', collect());

        expect($team->sports_html_entities)
            ->toBeInstanceOf(HtmlString::class)
            ->and($team->sports_html_entities->toHtml())->toBe('');
    });

    test('returns the sport html entity when team has one sport', function () {
        $team = new Team;
        $teamSport = new TeamSport(['sport' => Sport::BASKETBALL]);
        $team->setRelation('sports', collect([$teamSport]));

        expect($team->sports_html_entities)
            ->toBeInstanceOf(HtmlString::class)
            ->and($team->sports_html_entities->toHtml())->toBe(HtmlEntity::BASKETBALL->entity());
    });

    test('returns space separated sport html entities when team has multiple sports', function () {
        $team = new Team;
        $teamSport1 = new TeamSport(['sport' => Sport::BASKETBALL]);
        $teamSport2 = new TeamSport(['sport' => Sport::FOOTBALL]);
        $team->setRelation('sports', collect([$teamSport1, $teamSport2]));

        expect($team->sports_html_entities)
            ->toBeInstanceOf(HtmlString::class)
            ->and($team->sports_html_entities->toHtml())->toBe(
                HtmlEntity::BASKETBALL->entity().' '.HtmlEntity::FOOTBALL->entity()
            );
    });
});

describe('color_badge', function () {
    test('returns null when team has no color', function () {
        $team = new Team;

        expect($team->color_badge)->toBeNull();
    });

    test('returns raw color string when color is not a valid hex value', function () {
        $team = new Team(['color' => 'Carolina Blue']);

        expect($team->color_badge)->toBe('Carolina Blue');
    });

    test('returns a styled html badge when color is a valid hex value', function () {
        $team = new Team(['color' => '#4B9CD3']);

        expect($team->color_badge)
            ->toBeInstanceOf(HtmlString::class)
            ->and($team->color_badge->toHtml())
            ->toContain('background-color: #4B9CD3;')
            ->toContain('#4B9CD3');
    });
});

describe('logo_badge', function () {
    test('returns null when team has no logos', function () {
        $team = new Team(['designation' => 'Tar Heels', 'logos' => null]);

        expect($team->logo_badge)->toBeNull();
    });

    test('returns null when first logo is not a valid url', function () {
        $team = new Team(['designation' => 'Tar Heels', 'logos' => ['not-a-url']]);

        expect($team->logo_badge)->toBeNull();
    });

    test('returns a styled html logo badge when first logo is a valid url', function () {
        $team = new Team([
            'designation' => 'Tar Heels',
            'logos' => ['https://example.test/logo-primary.png'],
        ]);

        expect($team->logo_badge)
            ->toBeInstanceOf(HtmlString::class)
            ->and($team->logo_badge->toHtml())
            ->toContain('src="https://example.test/logo-primary.png"')
            ->toContain('alt="Tar Heels logo"');
    });
});

describe('display_name', function () {
    test('returns full display name with abbreviation when all fields are present', function () {
        $team = new Team([
            'organization' => 'North Carolina',
            'designation' => 'Tar Heels',
            'conference' => 'ACC',
            'abbreviation' => 'UNC',
        ]);

        expect($team->display_name)->toBe('North Carolina Tar Heels (UNC)');
    });

    test('returns name only when abbreviation is missing', function () {
        $team = new Team([
            'organization' => 'North Carolina',
            'designation' => 'Tar Heels',
            'conference' => null,
            'abbreviation' => '',
        ]);

        expect($team->display_name)->toBe('North Carolina Tar Heels');
    });

    test('returns unknown team when organization and designation are blank', function () {
        $team = new Team([
            'organization' => ' ',
            'designation' => null,
            'conference' => 'ACC',
            'abbreviation' => 'UNC',
        ]);

        expect($team->display_name)->toBe('Unknown Team (UNC)');
    });
});
