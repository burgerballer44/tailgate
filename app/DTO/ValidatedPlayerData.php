<?php

namespace App\DTO;

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