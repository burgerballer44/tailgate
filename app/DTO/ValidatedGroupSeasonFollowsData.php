<?php

namespace App\DTO;

/**
 * Represents normalized explicit season-follow input for a group.
 */
readonly class ValidatedGroupSeasonFollowsData
{
    /**
     * @param  array<int, int>  $season_ids  Explicitly followed season IDs.
     */
    public function __construct(
        public array $season_ids,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            season_ids: array_values(array_map('intval', (array) ($data['season_ids'] ?? []))),
        );
    }
}
