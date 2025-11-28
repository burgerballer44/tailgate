<?php

namespace App\DTO;

use App\Models\Common\DateOrString;
use App\Models\Common\TimeOrString;

readonly class ValidatedGameData
{
    public function __construct(
        public int $season_id,
        public int $home_team_id,
        public int $away_team_id,
        public int $home_team_score,
        public int $away_team_score,
        public DateOrString $start_date,
        public TimeOrString $start_time,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            season_id: (int) $data['season_id'],
            home_team_id: (int) $data['home_team_id'],
            away_team_id: (int) $data['away_team_id'],
            home_team_score: (int) $data['home_team_score'],
            away_team_score: (int) $data['away_team_score'],
            start_date: $data['start_date'] instanceof DateOrString ? $data['start_date'] : DateOrString::fromString($data['start_date']),
            start_time: $data['start_time'] instanceof TimeOrString ? $data['start_time'] : TimeOrString::fromString($data['start_time']),
        );
    }
}