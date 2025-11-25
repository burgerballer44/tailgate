<?php

use App\Models\UserRole;
use App\Models\UserStatus;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $this->get('/register')->assertStatus(200);
});

test('guests can register', function () {
    $this->post('/register', [
        'name' => 'Regular User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

test('guests that register have a pending status', function () {
    $this->post('/register', [
        'name' => 'Regular User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = Auth::user();

    expect($user->status)->toBe(UserStatus::PENDING->value);
});

test('guests that register have a regular role', function () {
    $this->post('/register', [
        'name' => 'Regular User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = Auth::user();

    expect($user->role)->toBe(UserRole::REGULAR->value);
});

test('the ulid field is populated when a guest registers', function () {
    $this->post('/register', [
        'name' => 'Regular User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = Auth::user();

    expect(Str::isUlid((string) $user->ulid))->toBeTrue();
});
