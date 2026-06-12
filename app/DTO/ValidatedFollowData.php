<?php

namespace App\DTO;

use App\Models\Sport;

/**
 * Holds validated data for a team follow relationship, including the team ID and optional sport filter.
 * This DTO represents a user's subscription to updates for a specific team and optionally a specific sport.
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
