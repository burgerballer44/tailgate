<?php

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('email verification screen can be rendered', function() {
    $user = User::factory()->create(['email_verified_at' => null]);
    $this->actingAs($user)->get('/verify-email')->assertStatus(200);
});

test('email can be verified', function() {
    $user = signInRegularUser(User::factory()->create(['email_verified_at' => null]));

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($verificationUrl)->assertRedirect(RouteServiceProvider::HOME.'?verified=1');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email is not verified with invalid hash', function() {
    $user = signInRegularUser(User::factory()->create(['email_verified_at' => null]));

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});