<?php

namespace App\Services;

use App\Models\User;
use App\DTO\ValidatedUserData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Database\Eloquent\Builder;

class UserService
{
    /**
     * Create a new user with the provided data.
     * This method handles user creation logic, including password hashing.
     *
     * @param  ValidatedUserData  $data  Validated user data including name, email, password, status, role.
     * @return User The created user instance.
     */
    public function create(ValidatedUserData $data): User
    {
        $userData = [
            'name' => $data->name,
            'email' => $data->email,
            'password' => self::hashPassword($data->password),
            'status' => $data->status->value,
            'role' => $data->role->value,
        ];

        return User::create($userData);
    }

    /**
     * Update an existing user's information in the system.
     * This method is used to modify user details such as name, email, role, or status,
     * and optionally update the password (hashing it if provided and filled).
     *
     * @param  User  $user  The user to update.
     * @param  ValidatedUserData  $data  Validated data to update the user with. If 'password' is present and filled, it will be hashed.
     * @param  array  $extra  Additional data to update, not part of validated user data.
     */
    public function update(User $user, ValidatedUserData $data, array $extra = []): void
    {
        // User data properties are never expected to be null or set to null.
        // The $extra array can contain any additional fields to update including null values.

        // remove null values
        $updateData = array_filter([
            'name'   => $data->name,
            'email'  => $data->email,
            'status' => $data->status?->value,
            'role'   => $data->role?->value,
        ], static fn ($value) => $value !== null);

        // handle password separately
        if (null !== $data->password && filled($data->password)) {
            $updateData['password'] = self::hashPassword($data->password);
        }

        // $updateData takes precedence over $extra
        $user->fill($updateData + $extra);
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
     * Hash a plain text password.
     * This utility method hashes the provided password using the framework's hashing mechanism.
     *
     * @param  string  $password  The plain text password to hash.
     * @return string The hashed password.
     */
    public static function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * Check if a plain text password matches a hashed password.
     * This utility method verifies if the provided plain text password corresponds to the given hashed password.
     *
     * @param  string  $password  The plain text password to check.
     * @param  string  $hashedPassword  The hashed password to compare against.
     * @return bool True if the passwords match, false otherwise.
     */
    public static function checkPassword(string $password, string $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }

    /**
     * Filter users based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param  array  $query  An associative array of query parameters to filter users.
     * @return Builder A query builder instance for the filtered users.
     */
    public function query(array $query)
    {
        return User::filter($query);
    }
}
