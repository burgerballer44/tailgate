<?php

use function Pest\Laravel\get;

beforeEach(function () {
    signInDeveloperUser();
});

describe('index', function () {
    test('shows the prediction rules page', function () {
        $response = get(route('developer.rules.index'));

        $response->assertOk();
        $response->assertViewIs('developer.rules.index');
        $response->assertSee('Prediction Rules', false);
        $response->assertSee('Prediction lock time', false);
        $response->assertSee('Unique group prediction', false);
    });
});