<?php

namespace App\Services\Contracts;

use App\Models\User;
use App\DTO\ValidatedUserData;

interface UserCommandInterface
{
    public function create(ValidatedUserData $data): User;
    public function updateProfile(User $user, ValidatedUserData $data): User;
    public function changePassword(User $user, string $newPassword): User;
    public function resetPassword(User $user, string $newPassword, string $rememberToken): User;
    public function delete(User $user): void;
}