<?php

use App\Models\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('profile page is displayed', function () {
    $user = signInDeveloperUser();
    $this->get('/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = signInDeveloperUser();

    $this->patch('/profile', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => UserRole::DEVELOPER->value,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->role)->toBe(UserRole::DEVELOPER->value);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = signInDeveloperUser();

    $this->patch('/profile', [
        'name' => 'Test User',
        'email' => $user->email,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = signInDeveloperUser();

    $this->delete('/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();

    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = signInDeveloperUser();

    $this->from('/profile')->delete('/profile', ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    expect($user->fresh())->not->toBeNull();
});

test('profile update resets email_verified_at when email is changed ensuring that must be verified again', function () {
    $user = signInDeveloperUser();

    expect($user->email_verified_at)->not->toBeNull();

    $this->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => 'new@example.com',
    ])->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'profile-updated');

    $user->refresh();

    expect($user->email_verified_at)->toBeNull();
});
