<?php

namespace App\DTO;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents normalized game input used by scheduling and scoring workflows.
 * Encapsulates teams, season, scores, and start-time semantics in a persistence-ready shape.
 *
 * @param  int  $season_id  The ID of the season the game belongs to.
 * @param  int  $home_team_id  The ID of the home team.
 * @param  int  $away_team_id  The ID of the away team.
 * @param  int  $home_team_score  The final score of the home team.
 * @param  int  $away_team_score  The final score of the away team.
 * @param  string|null  $start_date_time  The start date and time of the game in normalized format, or null if unknown.
 * @param  bool  $start_time_tbd  Whether the start time is to be determined (TBD).
 */
readonly class ValidatedGameData
{
    public function __construct(
        public int $season_id,
        public int $home_team_id,
        public int $away_team_id,
        public int $home_team_score,
        public int $away_team_score,
        public ?string $start_date_time,
        public bool $start_time_tbd,
    ) {}

    public static function fromArray(array $data): self
    {
        $normalizedStartDateTime = self::normalizeStartDateTime($data['start_date_time'] ?? null);
        $explicitStartTimeTbd = (bool) ($data['start_time_tbd'] ?? false);

        $derivedStartTimeTbd = $normalizedStartDateTime === null
            || self::isDateOnly($data['start_date_time'] ?? null);

        return new self(
            season_id: (int) $data['season_id'],
            home_team_id: (int) $data['home_team_id'],
            away_team_id: (int) $data['away_team_id'],
            home_team_score: (int) $data['home_team_score'],
            away_team_score: (int) $data['away_team_score'],
            start_date_time: $normalizedStartDateTime,
            start_time_tbd: $derivedStartTimeTbd || $explicitStartTimeTbd,
        );
    }

    private static function normalizeStartDateTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmedValue = trim($value);

        if (self::isDateOnly($trimmedValue)) {
            return $trimmedValue.' 00:00:00';
        }

        $parsed = date_create_immutable($trimmedValue);

        if (! $parsed instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('start_date_time must be a valid date-time string or null.');
        }

        return $parsed->format('Y-m-d H:i:s');
    }

    private static function isDateOnly(mixed $value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value)) === 1;
    }
}
