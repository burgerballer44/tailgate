<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserStatus;
use App\Models\UserRole;
use Illuminate\Support\Str;
use App\DTO\ValidatedUserData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Database\Eloquent\Builder;

class UserService
{
    /**
     * Create a new user with the provided data.
     * This method handles user creation logic, including password hashing. If password is null, a random password is generated.
     *
     * @param  ValidatedUserData  $data  Validated user data including name, email, password, status, role.
     * @return User The created user instance.
     */
    public function create(ValidatedUserData $data): User
    {
        $password = $data->password ?? Str::random(15);

        $userData = [
            'name' => $data->name,
            'email' => $data->email,
            'password' => self::hashPassword($password),
            'status' => $data->status->value,
            'role' => $data->role->value,
        ];

        return User::create($userData);
    }

    /**
     * Update a user's profile information (name, email, status, role).
     * This method is used to modify user profile details.
     *
     * @param  User  $user  The user to update.
     * @param  ValidatedUserData  $data  Validated data containing profile information to update.
     * @return User The updated user instance.
     */
    public function updateProfile(User $user, ValidatedUserData $data): User
    {
        // User properties are never expected to be null or set to null.

        $updateData = [
            'name' => $data->name,
            'email' => $data->email,
            'status' => $data->status->value,
            'role' => $data->role->value,
        ];

        // handle password if provided
        if (null !== $data->password && filled($data->password)) {
            $updateData['password'] = self::hashPassword($data->password);
        }

        $user->fill($updateData);
        $user->save();

        return $user;
    }

    /**
     * Change a user's password.
     * This method updates the user's password after hashing it.
     *
     * @param  User  $user  The user whose password to change.
     * @param  string  $newPassword  The new plain text password.
     * @return User The updated user instance.
     */
    public function changePassword(User $user, string $newPassword): User
    {
        $user->password = self::hashPassword($newPassword);
        $user->save();

        return $user;
    }

    /**
     * Reset a user's password during password reset flow.
     * This method updates the user's password and sets the remember_token.
     *
     * @param  User  $user  The user whose password to reset.
     * @param  string  $newPassword  The new plain text password.
     * @param  string|null  $rememberToken  The remember token to set.
     * @return User The updated user instance.
     */
    public function resetPassword(User $user, string $newPassword, string $rememberToken): User
    {
        $user->password = self::hashPassword($newPassword);
        $user->remember_token = $rememberToken;

        $user->save();

        return $user;
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
