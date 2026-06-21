<?php

namespace App\DTO;

use App\Models\GroupRole;
use App\Models\MemberStatus;

/**
 * Represents normalized membership input for group member lifecycle operations.
 * Encodes target user, role, and approval status in a persistence-ready structure.
 */
readonly class ValidatedMemberData
{
    /**
     * @param int|null $user_id The ID of the user being added as a member, or null for pending invitations
     *     where the user has not yet been identified.
     * @param GroupRole|null $role The role defining the member's permissions within the group
     *     (e.g. Owner, Moderator, Member), or null to apply the group default.
     * @param MemberStatus|null $status The membership approval state; defaults to APPROVED for
     *     direct additions, may be PENDING for invitation flows.
     */
    public function __construct(
        public ?int $user_id,
        public ?GroupRole $role,
        public ?MemberStatus $status = MemberStatus::APPROVED,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * Accepts both raw string values and already-cast enum instances for role and status,
     * which allows the factory to be used in both HTTP and programmatic contexts.
     *
     * @param array<string, mixed> $data Raw input data containing optional user_id, role, and status fields.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            role: isset($data['role']) ? ($data['role'] instanceof GroupRole ? $data['role'] : GroupRole::from($data['role'])) : null,
            status: isset($data['status']) ? ($data['status'] instanceof MemberStatus ? $data['status'] : MemberStatus::from($data['status'])) : null,
        );
    }
}
