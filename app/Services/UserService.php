<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
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
     * Update an existing user's information in the system.
     * This method is used to modify user details such as name, email, role, or status,
     * and optionally update the password (hashing it if provided and filled).
     *
     * @param  User  $user  The user to update.
     * @param  array  $data  Validated data to update the user with. If 'password' is present and filled, it will be hashed.
     */
    public function update(User $user, array $data): void
    {
        if (isset($data['password']) && filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

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

    /**
     * Filter users based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter users.
     * @return Builder A query builder instance for the filtered users.
     */
    public function query(array $query) : Builder
    {
        return User::filter($query);
    }
}
