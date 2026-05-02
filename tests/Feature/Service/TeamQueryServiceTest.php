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
