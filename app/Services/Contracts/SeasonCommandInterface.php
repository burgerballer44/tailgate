<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedGameData;
use App\DTO\ValidatedSeasonData;
use App\Models\Game;
use App\Models\Season;

/**
 * Manages the complete lifecycle of seasons and their associated games.
 * Handles season creation and updates (name, sport, type, active status), and provides game creation
 * within a season context, supporting sports league season management.
 */
interface SeasonCommandInterface
{
    /**
     * Create a new season from validated input.
     *
     * @param ValidatedSeasonData $data The normalized season payload.
     * @return Season The created season instance.
     */
    public function create(ValidatedSeasonData $data): Season;

    /**
     * Update an existing season.
     *
     * @param Season $season The season to update.
     * @param ValidatedSeasonData $data The normalized season payload.
     * @return Season The updated season instance.
     */
    public function update(Season $season, ValidatedSeasonData $data): Season;

    /**
     * Delete a season.
     *
     * @param Season $season The season to delete.
     * @return void
     */
    public function delete(Season $season): void;

    /**
     * Create and attach a game inside a season.
     *
     * @param Season $season The season that will own the new game.
     * @param ValidatedGameData $data The normalized game payload.
     * @return Game The created game instance.
     */
    public function addGame(Season $season, ValidatedGameData $data): Game;
}
