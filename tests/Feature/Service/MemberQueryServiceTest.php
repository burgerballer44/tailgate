<?php

use App\Models\Member;
use App\Services\MemberQueryService;

beforeEach(function () {
    $this->service = new MemberQueryService();
    Member::truncate();
});

describe('query members', function () {
    test('returns query builder', function () {
        $result = $this->service->query([]);

        expect($result)->toBeInstanceOf(\Illuminate\Contracts\Database\Eloquent\Builder::class);
        expect($result->getModel())->toBeInstanceOf(Member::class);
    });
});