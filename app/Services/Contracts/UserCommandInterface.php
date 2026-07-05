<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedUserData;
use App\Models\User;

/**
 * Defines write operations for user accounts.
 *
 * Implementations are responsible for user creation, profile updates,
 * credential changes, and account deletion.
 */
interface UserCommandInterface
{
    /**
     * Create a new user from validated input.
     *
     * @param  ValidatedUserData  $data  The normalized user payload.
     * @return User The created user instance.
     */
    public function create(ValidatedUserData $data): User;

    /**
     * Update the profile fields for an existing user.
     *
     * @param  User  $user  The user to update.
     * @param  ValidatedUserData  $data  The normalized user payload.
     * @return User The updated user instance.
     */
    public function updateProfile(User $user, ValidatedUserData $data): User;

    /**
     * Replace a user's password.
     *
     * @param  User  $user  The user whose password should change.
     * @param  string  $newPassword  The new plain-text password.
     * @return User The updated user instance.
     */
    public function changePassword(User $user, string $newPassword): User;

    /**
     * Reset a user's password and remember token.
     *
     * @param  User  $user  The user whose password should be reset.
     * @param  string  $newPassword  The new plain-text password.
     * @param  string  $rememberToken  The new remember token.
     * @return User The updated user instance.
     */
    public function resetPassword(User $user, string $newPassword, string $rememberToken): User;

    /**
     * Delete a user.
     *
     * @param  User  $user  The user to delete.
     */
    public function delete(User $user): void;
}
