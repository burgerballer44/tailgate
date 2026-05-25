<?php

use App\Models\Team;
use App\Services\TeamQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    $this->service = new TeamQueryService;
});

describe('query teams', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(Builder::class);
        expect($query->getModel())->toBeInstanceOf(Team::class);
    });

    test('includes sports relationship', function () {
        $query = $this->service->query([]);

        // Check that the query includes the sports relationship
        $eagerLoads = $query->getEagerLoads();
        expect($eagerLoads)->toHaveKey('sports');
    });

    test('search query can match by conference', function () {
        Team::factory()->create(['conference' => 'SEC']);
        Team::factory()->create(['conference' => 'Big Ten']);

        $teams = $this->service->query(['q' => 'SEC'])->get();

        expect($teams)->toHaveCount(1)
            ->and($teams->first()->conference)->toBe('SEC');
    });
});

describe('get available teams for follow', function () {
    test('returns teams ordered for descriptive dropdown display', function () {
        Team::factory()->create([
            'organization' => 'North Carolina',
            'designation' => 'Tar Heels',
            'conference' => 'ACC',
            'abbreviation' => 'UNC',
        ]);

        Team::factory()->create([
            'organization' => 'Alabama',
            'designation' => 'Crimson Tide',
            'conference' => 'SEC',
            'abbreviation' => 'BAMA',
        ]);

        $teams = $this->service->getAvailableTeamsForFollow();

        expect($teams->pluck('display_name')->all())->toBe([
            'Alabama Crimson Tide (BAMA)',
            'North Carolina Tar Heels (UNC)',
        ]);
    });

    test('exposes display_name accessor for follow form labels', function () {
        Team::factory()->create([
            'organization' => 'Duke',
            'designation' => 'Blue Devils',
            'conference' => 'ACC',
            'abbreviation' => 'DUKE',
        ]);

        $team = $this->service->getAvailableTeamsForFollow()->first();

        expect($team)->not->toBeNull()
            ->and($team->display_name)->toBe('Duke Blue Devils (DUKE)');
    });
});
