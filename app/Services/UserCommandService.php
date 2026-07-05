<?php

namespace App\Services;

use App\DTO\ValidatedUserData;
use App\Models\User;
use App\Services\Contracts\UserCommandInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Executes user account lifecycle actions, including creation, profile edits, and credential changes.
 * Ensures password handling and account updates remain centralized and consistent.
 */
class UserCommandService implements UserCommandInterface
{
    /**
     * Persists a new user account and guarantees credential hashing.
     * Generates a random password when one is not supplied.
     *
     * @param  ValidatedUserData  $data  Validated user data including profile and account state.
     * @return User The created user instance.
     */
    public function create(ValidatedUserData $data): User
    {
        // Generate a random password only when none was explicitly provided.
        $password = $data->password ?? Str::random(15);

        // Build the persistence payload with hashed credentials and normalized enums.
        $userData = [
            'name' => $data->name,
            'email' => $data->email,
            'password' => self::hashPassword($password),
            'status' => $data->status->value,
            'role' => $data->role->value,
        ];

        // Persist and return the new user account.
        return User::create($userData);
    }

    /**
     * Applies profile metadata changes to an existing user account.
     *
     * @param  User  $user  The user to update.
     * @param  ValidatedUserData  $data  Validated data to apply to the user.
     * @return User The updated user instance.
     */
    public function updateProfile(User $user, ValidatedUserData $data): User
    {
        // User properties are never expected to be null or set to null.

        // Apply profile fields directly from validated DTO input.
        $updateData = [
            'name' => $data->name,
            'email' => $data->email,
            'status' => $data->status->value,
            'role' => $data->role->value,
        ];

        // handle password if provided
        if ($data->password !== null && filled($data->password)) {
            $updateData['password'] = self::hashPassword($data->password);
        }

        // Persist all profile changes in one write.
        $user->fill($updateData);
        $user->save();

        return $user;
    }

    /**
     * Replaces a user's password using the application's hashing strategy.
     *
     * @param  User  $user  The user whose password should change.
     * @param  string  $newPassword  The new plain-text password.
     * @return User The updated user instance.
     */
    public function changePassword(User $user, string $newPassword): User
    {
        // Replace the stored hash using the same hashing strategy as account creation.
        $user->password = self::hashPassword($newPassword);
        $user->save();

        return $user;
    }

    /**
     * Completes password-reset persistence, including remember-token rotation.
     *
     * @param  User  $user  The user whose password should be reset.
     * @param  string  $newPassword  The new plain-text password.
     * @param  string  $rememberToken  The remember token to persist after the reset.
     * @return User The updated user instance.
     */
    public function resetPassword(User $user, string $newPassword, string $rememberToken): User
    {
        // Rotate both password hash and remember token as part of reset semantics.
        $user->password = self::hashPassword($newPassword);
        $user->remember_token = $rememberToken;

        $user->save();

        return $user;
    }

    /**
     * Removes a user account from persistence.
     *
     * @param  User  $user  The user to delete.
     */
    public function delete(User $user): void
    {
        // Delete by key to avoid side effects on the in-memory model.
        User::destroy($user->getKey());
    }

    /**
     * Produces the framework-standard hash for a plain-text password.
     *
     * @param  string  $password  The plain-text password to hash.
     * @return string The hashed password.
     */
    public static function hashPassword(string $password): string
    {
        // Keep hashing strategy centralized so credential handling is consistent.
        return Hash::make($password);
    }

    /**
     * Verifies a plain-text password against a stored hash.
     *
     * @param  string  $password  The plain-text password to check.
     * @param  string  $hashedPassword  The stored hashed password.
     * @return bool True when the passwords match.
     */
    public static function checkPassword(string $password, string $hashedPassword): bool
    {
        // Use framework comparison to avoid custom timing-unsafe comparisons.
        return Hash::check($password, $hashedPassword);
    }
}
