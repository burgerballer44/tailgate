<?php

namespace App\DTO;

use App\Models\Sport;

/**
 * Represents normalized follow input used by group team-follow workflows.
 * Encodes a target team and optional sport scope for follow persistence.
 */
readonly class ValidatedFollowData
{
    /**
     * @param  int  $team_id  The ID of the team being followed.
     * @param  Sport|null  $sport  The sport to scope the follow to, or null to follow the team across all sports.
     */
    public function __construct(
        public int $team_id,
        public ?Sport $sport,
    ) {}

    /**
     * Constructs an instance from a raw associative array, typically from a validated form request.
     *
     * @param  array<string, mixed>  $data  Raw input data containing 'team_id' and optionally 'sport'.
     */
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
