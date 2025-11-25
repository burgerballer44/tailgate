<?php

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInRegularUser();
});

describe('index', function () {
    test('works', function () {
        // create additional users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // visit the index page
        $response = $this->get(route('users.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.users.index');

        // assert data is passed to view
        $response->assertViewHas('users');
        $response->assertViewHas('statuses');
        $response->assertViewHas('roles');

        // verify users are in the view data
        $viewUsers = $response->viewData('users');
        // including the signed in user
        expect($viewUsers)->toHaveCount(3);

        // verify statuses and roles are collections
        $statuses = $response->viewData('statuses');
        $roles = $response->viewData('roles');
        expect($statuses)->toBeInstanceOf(Collection::class);
        expect($roles)->toBeInstanceOf(Collection::class);
    });
});

describe('creat in a user', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('users.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.users.create');

        // assert data is passed to view
        $response->assertViewHas('roles');
        $response->assertViewHas('statuses');

        // verify roles and statuses are collections
        $statuses = $response->viewData('statuses');
        $roles = $response->viewData('roles');
        expect($statuses)->toBeInstanceOf(Collection::class);
        expect($roles)->toBeInstanceOf(Collection::class);
    });

    test('works', function () {
        // user data
        $userData = User::factory()->make()->toArray();
        $userData['password'] = 'password';
        $userData['password_confirmation'] = 'password';

        // there should be 1 user in the db
        $this->assertDatabaseCount('users', 1);

        // post the user data
        $response = $this->post(route('users.store'), $userData);

        // should redirect to index
        $response->assertRedirect(route('users.index'));

        // there should be 2 users in the db
        $this->assertDatabaseCount('users', 2);

        // verify user was created
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            'status' => $userData['status'],
            'role' => $userData['role'],
        ]);
    });

    test('flashes success message on store', function () {
        // user data
        $userData = User::factory()->make()->toArray();
        $userData['password'] = 'password';
        $userData['password_confirmation'] = 'password';

        // post the user data
        $this->post(route('users.store'), $userData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('User created successfully!');
    });
});

describe('viewing a user', function () {
    test('works', function () {
        // create a user
        $user = User::factory()->create();

        // visit the show page
        $response = $this->get(route('users.show', $user));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.users.show');

        // assert user is passed to view
        $response->assertViewHas('user', $user);
    });
});

describe('updating user', function () {
    test('shows edit form', function () {
        // create a user
        $user = User::factory()->create();

        // visit the edit page
        $response = $this->get(route('users.edit', $user));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('admin.users.edit');

        // assert data is passed to view
        $response->assertViewHas('user', $user);
        $response->assertViewHas('roles');
        $response->assertViewHas('statuses');

        // verify roles and statuses are collections
        $statuses = $response->viewData('statuses');
        $roles = $response->viewData('roles');
        expect($statuses)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($roles)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    test('updates a user', function () {
        // create a user
        $user = User::factory()->create();

        // update data
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::ADMIN->value,
        ];

        // patch the user data
        $response = $this->patch(route('users.update', $user), $updateData);

        // should redirect to index
        $response->assertRedirect(route('users.index'));

        // verify user was updated
        $user->refresh();
        expect($user->name)->toBe($updateData['name']);
        expect($user->email)->toBe($updateData['email']);
        expect($user->status)->toBe($updateData['status']);
        expect($user->role)->toBe($updateData['role']);
    });


    test('flashes success message on update', function () {
        // create a user
        $user = User::factory()->create();

        // update data
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::ADMIN->value,
        ];

        // patch the user data
        $this->patch(route('users.update', $user), $updateData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('User updated successfully!');
    });
});

describe('deleting a user', function () {
    test('works', function () {
        // create a user
        $user = User::factory()->create();

        // there should be 2 users in the db
        $this->assertDatabaseCount('users', 2);

        // delete the user
        $response = $this->delete(route('users.destroy', $user));

        // should redirect to index
        $response->assertRedirect(route('users.index'));

        // there should be 1 user in the db
        $this->assertDatabaseCount('users', 1);

        // verify user was deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    test('flashes success message on delete', function () {
        // create a user
        $user = User::factory()->create();

        // delete the user
        $this->delete(route('users.destroy', $user))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('User deleted successfully!');
    });
});