<?php

namespace App\DTO;

use DateTimeImmutable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Represents the complete results payload for a group-season context.
 */
readonly class SeasonResultsViewData implements Arrayable
{
    /**
     * @param  array<int, PlayerLeaderboardRowData>  $leaderboardRows
     * @param  array<int, GameRawPredictionData>  $rawGameRows
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $groupId,
        public int $seasonId,
        public string $pointsPolicy,
        public DateTimeImmutable $generatedAt,
        public array $leaderboardRows,
        public array $rawGameRows,
        public array $meta = [],
    ) {}

    /**
     * Convert the view data to an array suitable for response payloads.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'season_id' => $this->seasonId,
            'points_policy' => $this->pointsPolicy,
            'generated_at' => $this->generatedAt->format(DATE_ATOM),
            'leaderboard_rows' => array_map(
                static fn (PlayerLeaderboardRowData $row): array => $row->toArray(),
                $this->leaderboardRows,
            ),
            'raw_game_rows' => array_map(
                static fn (GameRawPredictionData $row): array => $row->toArray(),
                $this->rawGameRows,
            ),
            'meta' => $this->meta,
        ];
    }
}
