<?php

namespace App\DTO;

/**
 * Represents normalized group input used by group lifecycle workflows.
 * Captures ownership and optional membership/player constraints for persistence.
 *
 * @param  string  $name  The name of the group.
 * @param  int  $owner_id  The ID of the user who owns the group.
 * @param  int|null  $member_limit  The maximum number of members allowed in the group, or null for no limit.
 * @param  int|null  $player_limit  The maximum number of players allowed in the group, or null for no limit.
 */
readonly class ValidatedGroupData
{
    public function __construct(
        public string $name,
        public int $owner_id,
        public ?int $member_limit,
        public ?int $player_limit,
    ) {}

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
