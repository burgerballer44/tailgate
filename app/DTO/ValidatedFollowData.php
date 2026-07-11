<?php

namespace App\DTO;

/**
 * Represents normalized follow input used by group team-follow workflows.
 * Encodes a target team and selected seasons for follow persistence.
 */
readonly class ValidatedFollowData
{
    /**
     * @param  int  $team_id  The ID of the team being followed.
     * @param  array<int, int>  $season_ids  Active season IDs selected for this follow action.
     */
    public function __construct(
        public int $team_id,
        public array $season_ids,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * @param  array<string, mixed>  $data  Raw input data containing 'team_id' and 'season_ids'.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            team_id: (int) $data['team_id'],
            season_ids: array_values(array_unique(array_map(
                static fn (mixed $seasonId): int => (int) $seasonId,
                (array) ($data['season_ids'] ?? []),
            ))),
        );
    }
}
