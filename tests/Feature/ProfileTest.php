<?php

use App\Models\User;
use Tests\TestCase;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('profile page is displayed', function() {
    $user = signInRegularUser();
    $this->get('/profile')->assertOk();
});

test('profile information can be updated', function() {
    $user = signInRegularUser();

    $this->patch('/profile', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ])
    ->assertSessionHasNoErrors()
    ->assertRedirect('/profile');

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function() {
    $user = signInRegularUser();

    $this->patch('/profile', [
        'name' => 'Test User',
        'email' => $user->email,
    ])
    ->assertSessionHasNoErrors()
    ->assertRedirect('/profile');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function() {
    $user = signInRegularUser();

    $this->delete('/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function() {
    $user = signInRegularUser();

    $this->from('/profile')->delete('/profile', ['password' => 'wrong-password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    expect($user->fresh())->not->toBeNull();
});