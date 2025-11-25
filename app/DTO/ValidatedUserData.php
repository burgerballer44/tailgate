<?php

namespace App\DTO;

use App\Models\UserRole;
use App\Models\UserStatus;

readonly class ValidatedUserData
{
    public function __construct(
        public ?string $name,
        public ?string $email,
        public ?string $password,
        public ?UserStatus $status,
        public ?UserRole $role,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            status: isset($data['status'])
                ? ($data['status'] instanceof UserStatus
                    ? $data['status']
                    : UserStatus::tryFrom($data['status']))
                : null,
            role: isset($data['role'])
                ? ($data['role'] instanceof UserRole
                    ? $data['role']
                    : UserRole::tryFrom($data['role']))
                : null,
        );
    }
}