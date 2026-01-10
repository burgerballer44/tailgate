<?php

namespace App\DTO;

use App\Models\GroupRole;
use App\Models\MemberStatus;

readonly class ValidatedMemberData
{
    public function __construct(
        public ?int $user_id,
        public ?GroupRole $role,
        public ?MemberStatus $status = MemberStatus::APPROVED,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            role: isset($data['role']) ? ($data['role'] instanceof GroupRole ? $data['role'] : GroupRole::from($data['role'])) : null,
            status: isset($data['status']) ? ($data['status'] instanceof MemberStatus ? $data['status'] : MemberStatus::from($data['status'])) : null,
        );
    }
}