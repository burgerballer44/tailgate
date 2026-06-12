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
    public function create(ValidatedSeasonData $data): Season;

    public function update(Season $season, ValidatedSeasonData $data): Season;

    public function delete(Season $season): void;

    public function addGame(Season $season, ValidatedGameData $data): Game;
}
