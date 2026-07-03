<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents the complete quick-prediction modal payload for a user.
 */
readonly class QuickPredictionPayload implements Arrayable
{
    /**
     * @param int $openPredictionCount Total number of unfilled prediction slots across all memberships.
     * @param int $totalGames Number of game entries included in this payload.
     * @param int $totalGroups Number of approved memberships included in this payload.
     * @param array<int, array<string, mixed>> $games Sorted array of game entry objects, each with group/game/players context.
     */
    public function __construct(
        public int $openPredictionCount,
        public int $totalGames,
        public int $totalGroups,
        public array $games,
    ) {}

    /**
     * Convert the payload to an array suitable for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => [
                'open_prediction_count' => $this->openPredictionCount,
                'total_games' => $this->totalGames,
                'total_groups' => $this->totalGroups,
            ],
            'games' => $this->games,
        ];
    }
}
