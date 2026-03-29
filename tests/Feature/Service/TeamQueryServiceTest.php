<?php

use App\Services\TeamQueryService;

beforeEach(function () {
    $this->service = new TeamQueryService();
});

describe('query teams', function () {
    test('returns query builder', function () {
        $query = $this->service->query([]);

        expect($query)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
        expect($query->getModel())->toBeInstanceOf(\App\Models\Team::class);
    });

    test('includes sports relationship', function () {
        $query = $this->service->query([]);

        // Check that the query includes the sports relationship
        $eagerLoads = $query->getEagerLoads();
        expect($eagerLoads)->toHaveKey('sports');
    });
});