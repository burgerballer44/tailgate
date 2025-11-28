<?php

namespace App\DTO;

use App\Models\UserRole;
use App\Models\UserStatus;

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