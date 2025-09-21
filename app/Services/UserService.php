<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Create a new user with the provided data.
     * This method handles user creation logic, including password hashing.
     *
     * @param  array  $data  Validated user data including name, email, password, status, role.
     * @return User The created user instance.
     */
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    /**
     * Update an existing user with the provided data.
     * This method updates user attributes and saves the changes.
     *
     * @param  User  $user  The user to update.
     * @param  array  $data  Validated data to update the user with.
     */
    public function update(User $user, array $data): void
    {
        $user->fill($data);
        $user->save();
    }

    /**
     * Delete a user from the system.
     * This method permanently removes the user.
     *
     * @param  User  $user  The user to delete.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }
}
