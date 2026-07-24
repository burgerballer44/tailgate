<?php

use App\Models\User;
use App\Models\Enums\UserRole;
use App\Models\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->user = signInDeveloperUser();
});

describe('index', function () {
    test('works', function () {
        // create additional users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // visit the index page
        $response = $this->get(route('developer.users.index'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.index');

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

    test('users can be filtered by role', function () {
        // create 2 developer users
        [$developer1, $developer2] = User::factory()->count(2)->create(['role' => UserRole::DEVELOPER->value]);
        // create 2 regular users
        [$regular1, $regular2] = User::factory()->count(2)->create(['role' => UserRole::REGULAR->value]);

        // get the developer users only
        $response = $this->get(route('developer.users.index', ['role' => 'Developer']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.index');

        // assert users are filtered
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        expect($users->count())->toBe(3); // includes signed in user
    });

    test('users can be filtered by status', function () {
        // create 2 active users
        [$active1, $active2] = User::factory()->count(2)->create(['status' => UserStatus::ACTIVE->value]);
        // create 2 pending users
        [$pending1, $pending2] = User::factory()->count(2)->create(['status' => UserStatus::PENDING->value]);

        // get the active users only
        $response = $this->get(route('developer.users.index', ['status' => 'Active']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.index');

        // assert users are filtered
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        // includes signed in user
        expect($users->count())->toBe(3);
    });

    test('users can be filtered by q for name', function () {
        // thing to find
        $q = 'FindMe';

        // create a user
        $user = User::factory()->create(['name' => $q]);
        $differentUserToNotFind = User::factory()->create(['name' => 'somethingelse']);

        // get the user
        $response = $this->get(route('developer.users.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.index');

        // assert users are filtered
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        expect($users->count())->toBe(1);
    });

    test('users can be filtered by q for email', function () {
        // thing to find
        $q = 'FindMe';

        // create a user
        $user = User::factory()->create(['email' => $q]);
        $differentUserToNotFind = User::factory()->create(['email' => 'somethingelse']);

        // get the user
        $response = $this->get(route('developer.users.index', ['q' => $q]));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.index');

        // assert users are filtered
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        expect($users->count())->toBe(1);
    });

    test('users returns empty when filter matches nothing', function () {
        // create a user
        User::factory()->create(['name' => 'John']);

        // search for something that doesn't exist
        $response = $this->get(route('developer.users.index', ['q' => 'NonExistent']));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.index');

        // assert users are filtered
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        expect($users->count())->toBe(0);
    });

});

describe('creating a user', function () {
    test('shows create form', function () {
        // visit the create page
        $response = $this->get(route('developer.users.create'));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.create');

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
        $response = $this->post(route('developer.users.store'), $userData);

        // should redirect to index
        $response->assertRedirect(route('developer.users.index'));

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

    test('works without password since a random one is generated', function () {
        // user data without password
        $userData = User::factory()->make()->toArray();

        // there should be 1 user in the db
        $this->assertDatabaseCount('users', 1);

        // post the user data
        $response = $this->post(route('developer.users.store'), $userData);

        // should redirect to index
        $response->assertRedirect(route('developer.users.index'));

        // there should be 2 users in the db
        $this->assertDatabaseCount('users', 2);

        // verify user was created with a password
        $createdUser = User::query()->where('email', $userData['email'])->first();
        expect($createdUser)->not->toBeNull();
        expect($createdUser->password)->not->toBeNull();
        expect($createdUser->password)->not->toBe('');
    });

    test('stores provided password when one is submitted', function () {
        $plainPassword = 'password123';
        $userData = User::factory()->make()->toArray();
        $userData['password'] = $plainPassword;
        $userData['password_confirmation'] = $plainPassword;

        $this->post(route('developer.users.store'), $userData)
            ->assertRedirect(route('developer.users.index'));

        $createdUser = User::query()->where('email', $userData['email'])->first();
        expect($createdUser)->not->toBeNull();
        expect(Hash::check($plainPassword, $createdUser->password))->toBeTrue();
    });

    test('returns validation error when password confirmation does not match', function () {
        $userData = User::factory()->make()->toArray();
        $userData['password'] = 'password123';
        $userData['password_confirmation'] = 'password321';

        $this->assertDatabaseCount('users', 1);

        $this->from(route('developer.users.create'))
            ->post(route('developer.users.store'), $userData)
            ->assertRedirect(route('developer.users.create'))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 1);
    });

    test('flashes success message on store', function () {
        // user data
        $userData = User::factory()->make()->toArray();
        $userData['password'] = 'password';
        $userData['password_confirmation'] = 'password';

        // post the user data
        $this->post(route('developer.users.store'), $userData)->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('User created successfully!');
    });
});

describe('viewing a user', function () {
    test('works', function () {
        // create a user
        $user = User::factory()->create();

        // visit the show page
        $response = $this->get(route('developer.users.show', $user));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.show');

        // assert user is passed to view
        $response->assertViewHas('user', $user);
    });
});

describe('updating user', function () {
    test('shows edit form', function () {
        // create a user
        $user = User::factory()->create();

        // visit the edit page
        $response = $this->get(route('developer.users.edit', $user));

        // assert successful response
        $response->assertOk();

        // assert view is returned
        $response->assertViewIs('developer.users.edit');

        // assert data is passed to view
        $response->assertViewHas('user', $user);
        $response->assertViewHas('roles');
        $response->assertViewHas('statuses');

        // verify roles and statuses are collections
        $statuses = $response->viewData('statuses');
        $roles = $response->viewData('roles');
        expect($statuses)->toBeInstanceOf(Collection::class);
        expect($roles)->toBeInstanceOf(Collection::class);
    });

    test('updates a user', function () {
        // create a user
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        // update data
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::DEVELOPER->value,
        ];

        // patch the user data
        $response = $this->patch(route('developer.users.update', $user), $updateData);

        // should redirect to index
        $response->assertRedirect(route('developer.users.index'));

        // verify user was updated
        $user->refresh();
        expect($user->name)->toBe($updateData['name']);
        expect($user->email)->toBe($updateData['email']);
        expect($user->status)->toBe($updateData['status']);
        expect($user->role)->toBe($updateData['role']);
    });

    test('updates user password when password is provided', function () {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ];

        $this->patch(route('developer.users.update', $user), $updateData)
            ->assertRedirect(route('developer.users.index'));

        $user->refresh();
        expect(Hash::check('new-password-456', $user->password))->toBeTrue();
    });

    test('returns validation error when updating with duplicate email', function () {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $userToUpdate = User::factory()->create(['email' => 'target@example.com']);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $existingUser->email,
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ];

        $this->from(route('developer.users.edit', $userToUpdate))
            ->patch(route('developer.users.update', $userToUpdate), $updateData)
            ->assertRedirect(route('developer.users.edit', $userToUpdate))
            ->assertSessionHasErrors('email');
    });

    test('flashes success message on update', function () {
        // create a user
        $user = User::factory()->create();

        // update data
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::DEVELOPER->value,
        ];

        // patch the user data
        $this->patch(route('developer.users.update', $user), $updateData)->assertRedirect();

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
        $response = $this->delete(route('developer.users.destroy', $user));

        // should redirect to index
        $response->assertRedirect(route('developer.users.index'));

        // there should be 1 user in the db
        $this->assertDatabaseCount('users', 1);

        // verify user was deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });

    test('flashes success message on delete', function () {
        // create a user
        $user = User::factory()->create();

        // delete the user
        $this->delete(route('developer.users.destroy', $user))->assertRedirect();

        // assert flash message
        expect(session('alert')['message'])->toBe('User deleted successfully!');
    });
});
