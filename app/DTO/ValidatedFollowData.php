<?php

namespace App\DTO;

use App\Models\Sport;

/**
 * Represents normalized follow input used by group team-follow workflows.
 * Encodes a target team and optional sport scope for follow persistence.
 *
 * @param  int  $team_id  The ID of the team being followed.
 * @param  Sport|null  $sport  The optional sport enum to filter updates, or null to follow all sports for the team.
 */
readonly class ValidatedFollowData
{
    public function __construct(
        public int $team_id,
        public ?Sport $sport,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            team_id: (int) $data['team_id'],
            sport: isset($data['sport']) && $data['sport'] !== null
                ? Sport::from((string) $data['sport'])
                : null,
        );
    }
}
