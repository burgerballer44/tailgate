<?php

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->get('/verify-email')->assertStatus(200);
});

test('email can be verified', function () {
    $user = signInRegularUser(User::factory()->unverified()->create());

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($verificationUrl)->assertRedirect(route('dashboard').'?verified=1');

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verifying an email address activates the user', function () {
    $user = signInRegularUser(User::factory()->pending()->unverified()->create());

    // the user status should start off as pending
    expect($user->status)->toBe(UserStatus::PENDING);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->get($verificationUrl)->assertRedirect(route('dashboard').'?verified=1');
    expect($user->status)->toBe(UserStatus::ACTIVE);
});

test('email is not verified with invalid hash', function () {
    $user = signInRegularUser(User::factory()->unverified()->create());

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
