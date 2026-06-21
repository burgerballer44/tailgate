<?php

namespace App\DTO;

/**
 * Represents normalized player input for roster management operations.
 * Encodes player identity and optional member linkage in a persistence-ready structure.
 */
readonly class ValidatedPlayerData
{
    /**
     * @param string $player_name The display name of the player.
     * @param int|null $member_id The ID of the group member associated with this player,
     *     or null if the player is not yet linked to a member account.
     */
    public function __construct(
        public string $player_name,
        public ?int $member_id,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * @param array<string, mixed> $data Raw input data containing 'player_name' and optionally 'member_id'.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            player_name: (string) $data['player_name'],
            member_id: isset($data['member_id']) ? (int) $data['member_id'] : null,
        );
    }
}
