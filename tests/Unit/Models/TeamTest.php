<?php

use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamSport;
use Illuminate\Support\Collection;

describe('sports_string ', function () {
    test('returns empty string when team has no sports', function () {
        $team = new Team();
        $team->setRelation('sports', collect());

        expect($team->sports_string)->toBe('');
    });

    test('returns single sport name when team has one sport', function () {
        $team = new Team();
        $teamSport = new TeamSport(['sport' => Sport::BASKETBALL]);
        $team->setRelation('sports', collect([$teamSport]));

        expect($team->sports_string)->toBe('Basketball');
    });

    test('returns comma separated sport names when team has multiple sports', function () {
        $team = new Team();
        $teamSport1 = new TeamSport(['sport' => Sport::BASKETBALL]);
        $teamSport2 = new TeamSport(['sport' => Sport::FOOTBALL]);
        $team->setRelation('sports', collect([$teamSport1, $teamSport2]));

        expect($team->sports_string)->toBe('Basketball, Football');
    });
});