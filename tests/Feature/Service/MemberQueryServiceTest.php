<?php

use App\Models\Member;
use App\Services\MemberQueryService;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    $this->service = new MemberQueryService;
    Member::truncate();
});

describe('query members', function () {
    test('returns query builder', function () {
        $result = $this->service->query([]);

        expect($result)->toBeInstanceOf(Builder::class);
        expect($result->getModel())->toBeInstanceOf(Member::class);
    });
});
