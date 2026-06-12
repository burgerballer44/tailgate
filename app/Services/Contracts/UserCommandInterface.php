<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedUserData;
use App\Models\User;

/**
 * Manages user account creation, profile updates, and password management.
 * Handles user account setup, profile modifications, password changes, and password resets,
 * supporting user authentication and account administration workflows.
 */
interface UserCommandInterface
{
    public function create(ValidatedUserData $data): User;

    public function updateProfile(User $user, ValidatedUserData $data): User;

    public function changePassword(User $user, string $newPassword): User;

    public function resetPassword(User $user, string $newPassword, string $rememberToken): User;

    public function delete(User $user): void;
}
