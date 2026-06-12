<?php

namespace App\DTO;

/**
 * Holds validated data for a group, including the group name, owner ID, and optional member and player limits.
 * This DTO represents group information that has been validated and normalized for database persistence.
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
