<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents one leaderboard row for a player in a season-scoped results view.
 */
readonly class PlayerLeaderboardRowData implements Arrayable
{
    /**
     * @param  int  $playerId  Internal player identifier.
     * @param  string  $playerName  Display name shown in leaderboard rows.
     * @param  float  $totalPoints  Aggregated season points for the player.
     * @param  int  $rank  Current leaderboard rank.
     * @param  int|null  $previousRank  Prior comparison rank (for change indicator).
     * @param  int  $rankChange  Rank delta derived from previous and current rank.
     * @param  float  $pointsBehindLeader  Positive difference from first-place total.
     */
    public function __construct(
        public int $playerId,
        public string $playerName,
        public float $totalPoints,
        public int $rank,
        public ?int $previousRank,
        public int $rankChange,
        public float $pointsBehindLeader,
    ) {}

    /**
     * Convert the row to an array suitable for response payloads.
     *
     * @return array<string, int|float|string|null>
     */
    public function toArray(): array
    {
        return [
            'player_id' => $this->playerId,
            'player_name' => $this->playerName,
            'total_points' => $this->totalPoints,
            'rank' => $this->rank,
            'previous_rank' => $this->previousRank,
            'rank_change' => $this->rankChange,
            'points_behind_leader' => $this->pointsBehindLeader,
        ];
    }
}
