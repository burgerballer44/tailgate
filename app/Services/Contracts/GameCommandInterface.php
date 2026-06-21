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
    /**
     * Create a new game from validated input.
     *
     * @param ValidatedGameData $data The normalized game payload.
     * @return Game The created game instance.
     */
    public function create(ValidatedGameData $data): Game;

    /**
     * Update an existing game.
     *
     * @param Game $game The game to update.
     * @param ValidatedGameData $data The normalized game payload.
     * @return void
     */
    public function update(Game $game, ValidatedGameData $data): void;

    /**
     * Delete a game.
     *
     * @param Game $game The game to delete.
     * @return void
     */
    public function delete(Game $game): void;
}
