<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\from;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

describe('coming soon bypass', function () {
    beforeEach(function () {
        config()->set('app.coming_soon', true);
        config()->set('app.coming_soon_bypass_code', 'alpha-2026');
    });

    test('guest routes are blocked when coming soon is enabled without bypass', function () {
        get('/')
            ->assertStatus(503)
            ->assertSee('Coming Soon');
    });

    test('alpha code entry page is accessible while coming soon is enabled', function () {
        get('/alpha')
            ->assertOk()
            ->assertSee('Closed alpha access');
    });

    test('invalid invite code does not grant bypass access', function () {
        from('/alpha')
            ->post('/alpha', ['invite_code' => 'wrong-code'])
            ->assertRedirect('/alpha')
            ->assertSessionHasErrors('invite_code');

        get('/')
            ->assertStatus(503)
            ->assertSee('Coming Soon');
    });

    test('valid invite code grants session bypass access', function () {
        post('/alpha', ['invite_code' => 'alpha-2026'])
            ->assertRedirect(route('home'))
            ->assertSessionHas('coming_soon_bypass_granted', true);

        get('/')
            ->assertOk();
    });
});
