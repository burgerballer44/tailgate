<?php

namespace App\DTO;

/**
 * Provides normalized input context for per-game points calculations.
 */
readonly class GamePointsContext
{
    /**
     * @param  int  $gameId  Game identifier for the calculation.
     * @param  int  $playerId  Player identifier being scored.
     * @param  int  $actualFollowedScore  Final followed-team score.
     * @param  int  $actualOpponentScore  Final opponent score.
     * @param  int|null  $predictedFollowedScore  Submitted followed-team prediction.
     * @param  int|null  $predictedOpponentScore  Submitted opponent prediction.
     * @param  int  $penaltyPoints  Additional penalty points applied to this game row.
     * @param  int|null  $placementRank  Optional precomputed placement rank for placement scoring.
     */
    public function __construct(
        public int $gameId,
        public int $playerId,
        public int $actualFollowedScore,
        public int $actualOpponentScore,
        public ?int $predictedFollowedScore,
        public ?int $predictedOpponentScore,
        public int $penaltyPoints = 0,
        public ?int $placementRank = null,
    ) {}
}
