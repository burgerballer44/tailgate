<?php

namespace App\DTO;

use App\Models\UserRole;
use App\Models\UserStatus;

/**
 * Represents normalized user input for account lifecycle operations.
 * Encodes identity, credentials, status, and role data in a consistent, typed structure.
 *
 * @param  string  $name  The full name of the user.
 * @param  string  $email  The email address of the user (should be validated as unique before persistence).
 * @param  string|null  $password  The hashed password, or null if not provided or unchanged.
 * @param  UserStatus  $status  The status enum indicating the user's account state (e.g., Active, Suspended).
 * @param  UserRole  $role  The role enum defining the user's permissions and access level (e.g., Admin, User).
 */
readonly class ValidatedUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public UserStatus $status,
        public UserRole $role,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: isset($data['password']) ? (string) $data['password'] : null,
            status: $data['status'] instanceof UserStatus ? $data['status'] : UserStatus::from($data['status']),
            role: $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']),
        );
    }
}
