<?php

namespace App\DTO;

readonly class ValidatedScoreData
{
    public function __construct(
        public ?int $player_id,
        public ?int $game_id,
        public int $home_team_prediction,
        public int $away_team_prediction,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            player_id: isset($data['player_id']) ? (int) $data['player_id'] : null,
            game_id: isset($data['game_id']) ? (int) $data['game_id'] : null,
            home_team_prediction: (int) $data['home_team_prediction'],
            away_team_prediction: (int) $data['away_team_prediction'],
        );
    }
}