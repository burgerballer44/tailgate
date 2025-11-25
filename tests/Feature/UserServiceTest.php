<?php

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Support\Str;
use App\Services\UserService;
use App\DTO\ValidatedUserData;

beforeEach(function () {
    $this->service = new UserService();
});

describe('create a user', function () {
    test('with valid data', function () {
        // create user data
        $data = User::factory()->make()->toArray();
        // set a known password
        $data['password'] = 'password';

        // ensure user does not exist
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);

        // try to create the user
        $user = $this->service->create(ValidatedUserData::fromArray($data));

        // verify user exists in database
        $this->assertDatabaseHas('users', ['email' => $data['email']]);

        // verify returned user data
        expect($user)->toBeInstanceOf(User::class);
        expect($user->name)->toBe($data['name']);
        expect($user->email)->toBe($data['email']);
        expect($user->status)->toBe($data['status']);
        expect($user->role)->toBe($data['role']);
        expect($user->updated_at)->not->toBeNull();
        expect($user->created_at)->not->toBeNull();
        expect(Str::isUlid((string)$user->ulid))->toBeTrue();
        expect($this->service->checkPassword('password', $user->password))->toBeTrue();
    });
});

describe('update a user', function () {
    test('with valid data without changing password', function () {
        // create existing user
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'status' => UserStatus::ACTIVE->value,
            'role' => UserRole::REGULAR->value,
        ]);

        // data to update to
        $data = ValidatedUserData::fromArray([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'status' => UserStatus::PENDING,
            'role' => UserRole::ADMIN,
        ]);

        // try to update the user
        $this->service->update($user, $data);

        $user->refresh();

        // verify updated data
        expect($user->name)->toBe($data->name);
        expect($user->email)->toBe($data->email);
        expect($user->status)->toBe($data->status->value);
        expect($user->role)->toBe($data->role->value);
    });

    test('changing password', function () {
        // create existing user with known password
        $user = User::factory()->create([
            'password' => $this->service->hashPassword('oldpassword')
        ]);

        // data to update password
        $data = ValidatedUserData::fromArray([
            'password' => 'newpassword',
        ]);

        // try to update the user password
        $this->service->update($user, $data);

        $user->refresh();

        expect($this->service->checkPassword('newpassword', $user->password))->toBeTrue();
        expect($this->service->checkPassword('oldpassword', $user->password))->toBeFalse();
    });

    test('does not update password if password is blank', function () {
        // create existing user with known password
        $user = User::factory()->create([
            'password' => $this->service->hashPassword('oldpassword')
        ]);

        // data with blank password
        $data = ValidatedUserData::fromArray([
            'password' => '',
            'name' => 'Updated Name',
        ]);

        // try to update the user
        $this->service->update($user, $data);

        $user->refresh();

        // verify name updated but password unchanged
        expect($user->name)->toBe($data->name);
        expect($this->service->checkPassword('oldpassword', $user->password))->toBeTrue();
    });

    test('update with empty data does nothing', function () {
        // create existing user
        $user = User::factory()->create();
        // capture original data
        $original = $user->toArray();

        // try to update with empty data
        $this->service->update($user, ValidatedUserData::fromArray([]));

        $user->refresh();
        $userArray = $user->toArray();

        // verify no changes
        expect($userArray['name'])->toBe($original['name']);
        expect($userArray['email'])->toBe($original['email']);
        expect($userArray['status'])->toBe($original['status']);
        expect($userArray['role'])->toBe($original['role']);
        expect($userArray['updated_at'])->toBe($original['updated_at']);
        expect($userArray['created_at'])->toBe($original['created_at']);
    });
});

describe('delete a user', function () {
    test('works', function () {
        // create user to delete
        $user = User::factory()->create();

        // verify user exists in database
        $this->assertDatabaseHas('users', ['email' => $user->email]);

        // try to delete the user
        $this->service->delete($user);

        // verify user is deleted from database
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });
});