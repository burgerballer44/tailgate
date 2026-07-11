<?php

namespace App\DTO;

/**
 * Represents normalized group input used by group lifecycle workflows.
 * Captures ownership and optional membership/player constraints for persistence.
 */
readonly class ValidatedGroupData
{
    /**
     * @param  string  $name  The name of the group.
     * @param  int  $owner_id  The ID of the user who owns the group.
     * @param  int|null  $member_limit  The maximum number of members allowed, or null for no limit.
     * @param  int|null  $player_limit  The maximum number of players allowed, or null for no limit.
     */
    public function __construct(
        public string $name,
        public int $owner_id,
        public ?int $member_limit,
        public ?int $player_limit,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * @param  array<string, mixed>  $data  Raw input data containing group name, owner, and optional limit fields.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            owner_id: (int) $data['owner_id'],
            member_limit: isset($data['member_limit']) ? (int) $data['member_limit'] : null,
            player_limit: isset($data['player_limit']) ? (int) $data['player_limit'] : null,
        );
    }
}
