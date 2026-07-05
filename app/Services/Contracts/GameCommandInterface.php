<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedGameData;
use App\Models\Game;

/**
 * Defines write operations for game records.
 *
 * Implementations are responsible for persisting validated game payloads,
 * including schedule and score updates.
 */
interface GameCommandInterface
{
    /**
     * Create a new game from validated input.
     *
     * @param  ValidatedGameData  $data  The normalized game payload.
     * @return Game The created game instance.
     */
    public function create(ValidatedGameData $data): Game;

    /**
     * Update an existing game.
     *
     * @param  Game  $game  The game to update.
     * @param  ValidatedGameData  $data  The normalized game payload.
     */
    public function update(Game $game, ValidatedGameData $data): void;

    /**
     * Delete a game.
     *
     * @param  Game  $game  The game to delete.
     */
    public function delete(Game $game): void;
}
