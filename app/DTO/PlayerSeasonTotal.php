<?php

namespace App\DTO;

/**
 * Represents aggregate season totals needed for policy-driven ranking.
 */
readonly class PlayerSeasonTotal
{
    /**
     * @param  int  $playerId  Player identifier.
     * @param  string  $playerName  Display name used in ranking output.
     * @param  float  $totalPoints  Aggregated season points for ranking.
     * @param  int|null  $previousRank  Previous computed rank for tie-break usage.
     */
    public function __construct(
        public int $playerId,
        public string $playerName,
        public float $totalPoints,
        public ?int $previousRank,
    ) {}
}
