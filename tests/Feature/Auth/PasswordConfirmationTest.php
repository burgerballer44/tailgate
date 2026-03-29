<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('confirm password screen can be rendered', function () {
    $user = signInDeveloperUser();
    $this->get('/confirm-password')->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = signInDeveloperUser();

    $response = $this->post('/confirm-password', [
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = signInDeveloperUser();

    $response = $this->post('/confirm-password', [
        'password' => 'wrong-password',
    ])
        ->assertSessionHasErrors();
});
