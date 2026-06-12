<?php

namespace App\DTO;

use App\Models\GroupRole;
use App\Models\MemberStatus;

/**
 * Represents normalized membership input for group member lifecycle operations.
 * Encodes target user, role, and approval status in a persistence-ready structure.
 *
 * @param  int|null  $user_id  The ID of the user being added as a member, or null for pending invitations.
 * @param  GroupRole|null  $role  The optional role enum defining the member's permissions within the group (e.g., Owner, Moderator, Member).
 * @param  MemberStatus  $status  The membership status enum indicating approval state (e.g., Approved, Pending, Rejected).
 */
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
