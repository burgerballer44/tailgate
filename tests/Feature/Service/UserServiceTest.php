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

describe('update user profile', function () {
    test('with valid data', function () {
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
            'password' => '',
            'status' => UserStatus::PENDING->value,
            'role' => UserRole::ADMIN->value,
        ]);

        // ensure updated user does not exist
        $this->assertDatabaseMissing('users', [
            'name' => $data->name,
            'email' => $data->email,
            'status' => $data->status->value,
            'role' => $data->role->value,
        ]);

        // try to update the user profile
        $updatedUser = $this->service->updateProfile($user, $data);

        // verify updated user exists in database
        $this->assertDatabaseHas('users', [
            'name' => $data->name,
            'email' => $data->email,
            'status' => $data->status->value,
            'role' => $data->role->value,
        ]);

        // verify returned user is the same instance
        expect($updatedUser)->toBe($user);

        // verify updated data
        expect($user->name)->toBe($data->name);
        expect($user->email)->toBe($data->email);
        expect($user->status)->toBe($data->status->value);
        expect($user->role)->toBe($data->role->value);
    });

});

describe('change user password', function () {
    test('with valid new password', function () {
        // create existing user with known password
        $user = User::factory()->create([
            'password' => $this->service->hashPassword('oldpassword')
        ]);

        // change password
        $updatedUser = $this->service->changePassword($user, 'newpassword');

        // verify returned user is the same instance
        expect($updatedUser)->toBe($user);

        expect($this->service->checkPassword('newpassword', $user->password))->toBeTrue();
        expect($this->service->checkPassword('oldpassword', $user->password))->toBeFalse();
    });
});

describe('reset user password', function () {
    test('with valid new password and remember token', function () {
        // create existing user with known password
        $user = User::factory()->create([
            'password' => $this->service->hashPassword('oldpassword'),
            'remember_token' => null,
        ]);

        expect($user->remember_token)->toBeNull();

        // reset password with remember token
        $updatedUser = $this->service->resetPassword($user, 'newpassword', 'newtoken123');

        // verify returned user is the same instance
        expect($updatedUser)->toBe($user);

        expect($this->service->checkPassword('newpassword', $user->password))->toBeTrue();
        expect($this->service->checkPassword('oldpassword', $user->password))->toBeFalse();
        expect($user->remember_token)->toBe('newtoken123');
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