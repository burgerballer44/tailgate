<?php

namespace App\DTO;

use App\Models\User;

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