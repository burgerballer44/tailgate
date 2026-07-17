<?php

namespace App\DTO;

/**
 * Represents the computed scoring result for one player in one game.
 */
readonly class PlayerGamePointsResult
{
    /**
     * @param  int  $playerId  Player identifier for this result row.
     * @param  int  $gameId  Game identifier for this result row.
     * @param  float  $points  Final computed points for the game.
     * @param  int  $penaltyPoints  Penalty points included in the total.
     * @param  array<int, string>  $calculationNotes
     */
    public function __construct(
        public int $playerId,
        public int $gameId,
        public float $points,
        public int $penaltyPoints,
        public array $calculationNotes = [],
    ) {}
}
