<?php

namespace App\DTO;

/**
 * Provides normalized input context when a player is missing a prediction.
 */
readonly class MissingPredictionContext
{
    /**
     * @param  int  $gameId  Game identifier for the missing prediction case.
     * @param  int  $playerId  Player identifier missing a submission.
     * @param  float|null  $worstSubmittedGamePoints  Worst submitted score in this game when available.
     * @param  float  $fallbackPoints  Policy-defined baseline when no submitted scores are available.
     */
    public function __construct(
        public int $gameId,
        public int $playerId,
        public ?float $worstSubmittedGamePoints,
        public float $fallbackPoints,
    ) {}
}
