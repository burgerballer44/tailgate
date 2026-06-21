<?php

namespace App\DTO;

use App\Models\UserRole;
use App\Models\UserStatus;

/**
 * Represents normalized user input for account lifecycle operations.
 * Encodes identity, credentials, status, and role data in a consistent, typed structure.
 */
readonly class ValidatedUserData
{
    /**
     * @param string $name The full name of the user.
     * @param string $email The email address of the user (must be unique; uniqueness should
     *     be validated before this DTO is persisted).
     * @param string|null $password The hashed password, or null if not provided or unchanged.
     * @param UserStatus $status The account state enum (e.g. Active, Suspended).
     * @param UserRole $role The role enum defining the user's permissions and access level (e.g. Admin, User).
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public UserStatus $status,
        public UserRole $role,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * Accepts both raw string values and already-cast enum instances for status and role,
     * which allows the factory to be used in both HTTP and programmatic contexts.
     *
     * @param array<string, mixed> $data Raw input data containing name, email, optional password, status, and role.
     * @return self
     */
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
