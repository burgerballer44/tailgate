<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents one game block in the raw prediction data tab.
 */
readonly class GameRawPredictionData implements Arrayable
{
    /**
     * @param  array<int, GameRawPredictionPlayerRowData>  $playerRows
     */
    public function __construct(
        public int $gameId,
        public string $weekLabel,
        public string $gameStatus,
        public string $followedTeam,
        public string $opponentTeam,
        public ?int $actualFollowedScore,
        public ?int $actualOpponentScore,
        public array $playerRows,
    ) {}

    /**
     * Convert the game block to an array suitable for response payloads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'game_id' => $this->gameId,
            'week_label' => $this->weekLabel,
            'game_status' => $this->gameStatus,
            'followed_team' => $this->followedTeam,
            'opponent_team' => $this->opponentTeam,
            'actual_followed_score' => $this->actualFollowedScore,
            'actual_opponent_score' => $this->actualOpponentScore,
            'player_rows' => array_map(
                static fn (GameRawPredictionPlayerRowData $row): array => $row->toArray(),
                $this->playerRows,
            ),
        ];
    }
}
