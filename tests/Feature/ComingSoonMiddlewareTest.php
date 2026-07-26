<?php

describe('coming soon middleware', function () {
    test('returns the requested page when coming soon mode is disabled', function () {
        config()->set('app.coming_soon', false);

        $this->get('/')->assertOk();
    });

    test('shows the coming soon page for all routes when enabled', function () {
        config()->set('app.coming_soon', true);

        $response = $this->get('/login');

        $response->assertStatus(503);
        $response->assertViewIs('coming-soon');
    });
});