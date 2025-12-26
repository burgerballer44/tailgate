<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('confirm password screen can be rendered', function () {
    $user = signInAdminUser();
    $this->get('/confirm-password')->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = signInAdminUser();

    $response = $this->post('/confirm-password', [
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = signInAdminUser();

    $response = $this->post('/confirm-password', [
        'password' => 'wrong-password',
    ])
        ->assertSessionHasErrors();
});
