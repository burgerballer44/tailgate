<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents one player's raw prediction details for a specific game.
 */
readonly class GameRawPredictionPlayerRowData implements Arrayable
{
    /**
     * @param  array<int, string>  $calculationNotes
     */
    public function __construct(
        public int $playerId,
        public string $playerName,
        public ?int $predictedFollowedScore,
        public ?int $predictedOpponentScore,
        public int $penaltyPoints,
        public float $gamePoints,
        public array $calculationNotes = [],
    ) {}

    /**
     * Convert the row to an array suitable for response payloads.
     *
     * @return array<string, int|float|string|null|array<int, string>>
     */
    public function toArray(): array
    {
        return [
            'player_id' => $this->playerId,
            'player_name' => $this->playerName,
            'predicted_followed_score' => $this->predictedFollowedScore,
            'predicted_opponent_score' => $this->predictedOpponentScore,
            'penalty_points' => $this->penaltyPoints,
            'game_points' => $this->gamePoints,
            'calculation_notes' => $this->calculationNotes,
        ];
    }
}
