<?php

namespace App\DTO;

/**
 * Holds validated data for a player, including the player name and optional member ID.
 * This DTO represents a player entity that may be associated with a group member.
 *
 * @param  string  $player_name  The name of the player.
 * @param  int|null  $member_id  The ID of the group member associated with this player, or null if unassociated.
 */
readonly class ValidatedPlayerData
{
    public function __construct(
        public string $player_name,
        public ?int $member_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            player_name: (string) $data['player_name'],
            member_id: isset($data['member_id']) ? (int) $data['member_id'] : null,
        );
    }
}
