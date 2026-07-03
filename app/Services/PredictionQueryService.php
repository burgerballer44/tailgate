<?php

namespace App\Services;

use App\Models\Prediction;
use App\Services\Contracts\PredictionQueryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Supports prediction retrieval for read-only workflows.
 */
class PredictionQueryService implements PredictionQueryInterface
{
    /**
     * Load predictions scoped to the provided players and games.
     *
     * @param EloquentCollection<int, \App\Models\Player> $players
     * @param Collection<int, \App\Models\Game> $games
     * @return EloquentCollection<int, Prediction>
     */
    public function getPredictionsForPlayersAndGames(EloquentCollection $players, Collection $games): EloquentCollection
    {
        if ($players->isEmpty() || $games->isEmpty()) {
            return new EloquentCollection;
        }

        return Prediction::query()
            ->whereIn('player_id', $players->pluck('id'))
            ->whereIn('game_id', $games->pluck('id'))
            ->get();
    }
}
