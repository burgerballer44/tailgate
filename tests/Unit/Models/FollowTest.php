<?php

use App\Models\Follow;

describe('route model binding', function () {
    test('uses ulid as the route key name', function () {
        $follow = new Follow;

        expect($follow->getRouteKeyName())->toBe('ulid');
    });
});
