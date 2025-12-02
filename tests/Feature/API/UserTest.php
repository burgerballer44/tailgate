<?php

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->user = actAsAPIUser();
});

describe ('user creation', function () {
    test('works', function () {
        // make data for a user
        $userData = User::factory()->make()->getAttributes();
        $userData['password_confirmation'] = $userData['password'];

        // there should be 1 user in the db
        $this->assertDatabaseCount('users', 1);

        // post the user data
        $this->post('api/v1/users', $userData)->assertCreated();

        // there should be 2 users in the db
        $this->assertDatabaseCount('users', 2);
    });

    test('the user is returned', function () {
        // make data for a user
        $userData = User::factory()->make()->getAttributes();
        $userData['password_confirmation'] = $userData['password'];

        // post the user data
        $this->post('api/v1/users', $userData)
            ->assertCreated()
            ->assertJson(['data' => [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'status' => $userData['status'],
                'role' => $userData['role'],
            ],
            ]);
    });

    test('the ulid field is populated', function () {
        // make data for a user
        $userData = User::factory()->make()->getAttributes();
        $userData['password_confirmation'] = $userData['password'];

        // post the user data
        $this->post('api/v1/users', $userData)->assertCreated();

        // get the user we posted
        $user = User::first();

        expect(Str::isUlid($user->ulid))->toBeTrue();
    });
});

describe('viewing a user', function () {
    test('works', function () {
        // create a user
        $user = User::factory()->create();

        // get the user
        $this->get("api/v1/users/{$user->ulid}")
            ->assertOk()
            ->assertJson(['data' => [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => $user->role,
            ],
            ]);
    });

    test('cannot be viewed by id', function () {
        // we want to catch the exception not see the pretty response
        $this->withoutExceptionHandling();

        // create a user
        $user = User::factory()->create();

        // get the user
        $this->get("api/v1/users/{$user->id}");
    })->throws(ModelNotFoundException::class);

    test('returns 404 for invalid ulid', function () {
        // invalid ulid
        $invalidUlid = 'invalid-ulid';

        // get the user
        $this->get("api/v1/users/{$invalidUlid}")->assertNotFound();
    });
});

describe('updating a user', function () {
    test('works', function () {
        // create a user
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        // set fields to update
        $data = [
            'name' => 'updatedName',
            'email' => 'updatedEmail@email.com',
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::ADMIN->value,
        ];

        // post the data
        $this->patch("api/v1/users/{$user->ulid}", $data)->assertNoContent();

        $user->refresh();

        expect($user->name)->toBe($data['name']);
        expect($user->email)->toBe($data['email']);
        expect($user->status)->toBe($data['status']);
        expect($user->role)->toBe($data['role']);
    });
});

describe('listing users', function () {
    test('works', function () {
        // create 2 users
        [$user1, $user2] = User::factory()->count(2)->create();

        // get the users
        $this->get('api/v1/users')
            ->assertOk()
            ->assertJson(['data' => [
                [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'status' => $this->user->status,
                    'role' => $this->user->role,
                ], [
                    'name' => $user1->name,
                    'email' => $user1->email,
                    'status' => $user1->status,
                    'role' => $user1->role,
                ], [
                    'name' => $user2->name,
                    'email' => $user2->email,
                    'status' => $user2->status,
                    'role' => $user2->role,
                ],
            ]]);
    });

    test('list of users can be filtered by role', function () {
        // create 2 admin users
        [$admin1, $admin2] = User::factory()->count(2)->create(['role' => UserRole::ADMIN->value]);
        // create 2 regular users
        [$regular1, $regular2] = User::factory()->count(2)->create(['role' => UserRole::REGULAR->value]);

        // get the admin users only
        $this->get('api/v1/users?role=Admin')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                [
                    'name' => $admin1->name,
                    'email' => $admin1->email,
                    'status' => $admin1->status,
                    'role' => $admin1->role,
                ], [
                    'name' => $admin2->name,
                    'email' => $admin2->email,
                    'status' => $admin2->status,
                    'role' => $admin2->role,
                ],
            ]]);
    });

    test('list of users can be filtered by status', function () {
        // create 2 active users
        [$active1, $active2] = User::factory()->count(2)->create(['status' => UserStatus::ACTIVE->value]);
        // create 2 pending users
        [$pending1, $pending2] = User::factory()->count(2)->create(['status' => UserStatus::PENDING->value]);

        // get the active users only
        $this->get('api/v1/users?status=Active')
            ->assertOk()
            // includes signed in user
            ->assertJsonCount(3, 'data')
            ->assertJson(['data' => [
                [
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'status' => $this->user->status,
                    'role' => $this->user->role,
                ], [
                    'name' => $active1->name,
                    'email' => $active1->email,
                    'status' => $active1->status,
                    'role' => $active1->role,
                ], [
                    'name' => $active2->name,
                    'email' => $active2->email,
                    'status' => $active2->status,
                    'role' => $active2->role,
                ],
            ]]);
    });

    test('list of users can be filtered by q for name', function () {
        // thing to find
        $q = 'FindMe';

        // create a user
        $user = User::factory()->create(['name' => $q]);
        $differentUserToNotFind = User::factory()->create(['name' => 'somethingelse']);

        // get the user
        $this->get("api/v1/users?q=$q")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role' => $user->role,
                ],
            ]]);
    });

    test('list of users can be filtered by q for email', function () {
        // thing to find
        $q = 'FindMe';

        // create a user
        $user = User::factory()->create(['email' => $q]);
        $differentUserToNotFind = User::factory()->create(['email' => 'somethingelse']);

        // get the user
        $this->get("api/v1/users?q=$q")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'role' => $user->role,
                ],
            ]]);
    });

    test('list of users returns empty when filter matches nothing', function () {
        // create a user
        User::factory()->create(['name' => 'John']);

        // search for something that doesn't exist
        $this->get('api/v1/users?q=NonExistent')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

});

describe('deleting a user', function () {
    test('works', function () {
        // create a user
        $user = User::factory()->create();

        // there should be 2 users in the db
        $this->assertDatabaseCount('users', 2);

        // delete the user
        $this->delete("api/v1/users/{$user->ulid}")->assertAccepted();

        // there should be 1 user in the db
        $this->assertDatabaseCount('users', 1);
    });
});
