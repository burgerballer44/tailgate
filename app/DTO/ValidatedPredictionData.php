<?php

namespace App\DTO;

/**
 * Represents normalized prediction input for player-game submissions.
 * Captures prediction values and optional player/game references in a persistence-ready shape.
 *
 * @param  int|null  $player_id  The ID of the player making the prediction, or null for system-generated predictions.
 * @param  int|null  $game_id  The ID of the game being predicted, or null if the game is not yet determined.
 * @param  int  $home_team_prediction  The predicted score for the home team.
 * @param  int  $away_team_prediction  The predicted score for the away team.
 */
readonly class ValidatedPredictionData
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