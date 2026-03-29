<?php

namespace App\Services\Contracts;

use App\Models\Game;
use App\Models\Season;
use App\DTO\ValidatedGameData;
use App\DTO\ValidatedSeasonData;

interface SeasonCommandInterface
{
    public function create(ValidatedSeasonData $data): Season;
    public function update(Season $season, ValidatedSeasonData $data): Season;
    public function delete(Season $season): void;
    public function addGame(Season $season, ValidatedGameData $data): Game;
}