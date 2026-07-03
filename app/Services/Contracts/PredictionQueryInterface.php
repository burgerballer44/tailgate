<?php

namespace App\Services\Contracts;

use App\Models\Prediction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Retrieves prediction records for read-only use cases.
 */
interface PredictionQueryInterface
{
    /**
     * Load predictions scoped to the provided players and games.
     *
     * @param EloquentCollection<int, \App\Models\Player> $players
     * @param Collection<int, \App\Models\Game> $games
     * @return EloquentCollection<int, Prediction>
     */
    public function getPredictionsForPlayersAndGames(EloquentCollection $players, Collection $games): EloquentCollection;
}
