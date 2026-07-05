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
     * @param  array<int, string>|null  $enabled_prediction_policies  Group-level prediction policy keys
     *                                                                enabled for this group, or null to inherit the application defaults.
     */
    public function __construct(
        public string $name,
        public int $owner_id,
        public ?int $member_limit,
        public ?int $player_limit,
        public ?array $enabled_prediction_policies = null,
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
            enabled_prediction_policies: isset($data['enabled_prediction_policies'])
                ? array_values(array_filter((array) $data['enabled_prediction_policies']))
                : null,
        );
    }
}
