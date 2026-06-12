<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedGameData;
use App\Models\Game;

/**
 * Manages the complete lifecycle of game data within a season.
 * Handles creation of new games, updates to game information (scores, date-time), and deletion of games,
 * supporting game scheduling and score management features.
 */
interface GameCommandInterface
{
    public function create(ValidatedGameData $data): Game;

    public function update(Game $game, ValidatedGameData $data): void;

    public function delete(Game $game): void;
}
