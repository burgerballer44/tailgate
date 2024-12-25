<?php

use App\Models\Sport;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function() {
    $this->user = actAsAPIUser();
});

test('a user can be created', function () {
    // make data for a user
    $userData = User::factory()->make()->getAttributes();
    $userData['password_confirmation'] = $userData['password'];

    // there should be 1 user in the db
    // $this->assertDatabaseCount('users', 1);

    // post the user data
    $this->post("api/v1/users", $userData)->assertCreated();

    // there should be 2 users in the db
    $this->assertDatabaseCount('users', 2);
});

test('the user is returned when a user is created', function () {
    // make data for a user
    $userData = User::factory()->make()->getAttributes();
    $userData['password_confirmation'] = $userData['password'];

    // post the user data
    $this->post("api/v1/users", $userData)
        ->assertCreated()
        ->assertJson(['data' => [
            'name'     => $userData['name'],
            'email'    => $userData['email'],
            'status'   => $userData['status'],
            'role'     => $userData['role'],
            ]
        ]);
});

test('the ulid field is populated when a user is created', function () {
    // make data for a user
    $userData = User::factory()->make()->getAttributes();
    $userData['password_confirmation'] = $userData['password'];

    // post the user data
    $this->post("api/v1/users", $userData)->assertCreated();

    // get the user we posted
    $user = User::first();

    expect(Str::isUlid($user->ulid))->toBeTrue();
});

test('a user can be viewed by ulid', function () {
    // create a user
    $user = User::factory()->create();

    // get the user
    $this->get("api/v1/users/{$user->ulid}")
        ->assertOk()
        ->assertJson(['data' => [
            'name'     => $user->name,
            'email'    => $user->email,
            'status'   => $user->status,
            'role'     => $user->role,
            ]
        ]);
});

test('a user cannot be viewed by id', function () {
    // we want to catch the exception not see the pretty response
    $this->withoutExceptionHandling();

    // create a user
    $user = User::factory()->create();

    // get the user
    $this->get("api/v1/users/{$user->id}");
})->throws(ModelNotFoundException::class);

test('a user can be updated', function () {
    // create a user
    $user = User::factory()->create();

    // set fields to update
    $data = [
        'name'   => 'updatedName',
        'email'  => 'updatedEmail@email.com',
        'status' => UserStatus::ACTIVE->value,
        'role'   => UserRole::REGULAR->value,
    ];

    // post the data
    $this->patch("api/v1/users/{$user->ulid}", $data)->assertNoContent();

    $user->refresh();
    
    expect($user->name)->toBe($data['name']);
    expect($user->email)->toBe($data['email']);
    expect($user->status)->toBe($data['status']);
    expect($user->role)->toBe($data['role']);
});

test('a lists of users can be retrieved', function () {
    // create 2 users
    [$user1, $user2] = User::factory()->count(2)->create();

    // get the users
    $this->get("api/v1/users")
        ->assertOk()
        ->assertJson(['data' => [
            [
                'name'     => $this->user->name,
                'email'    => $this->user->email,
                'status'   => $this->user->status,
                'role'     => $this->user->role,
            ],[
                'name'     => $user1->name,
                'email'    => $user1->email,
                'status'   => $user1->status,
                'role'     => $user1->role,
            ], [
                'name'     => $user2->name,
                'email'    => $user2->email,
                'status'   => $user2->status,
                'role'     => $user2->role,
            ]
        ]]);
});

test('a user can be deleted', function () {
    // create a user
    $user = User::factory()->create();

    // there should be 2 users in the db
    $this->assertDatabaseCount('users', 2);

    // delete the user
    $this->delete("api/v1/users/{$user->ulid}")->assertAccepted();

    // there should be 1 user in the db
    $this->assertDatabaseCount('users', 1);
});