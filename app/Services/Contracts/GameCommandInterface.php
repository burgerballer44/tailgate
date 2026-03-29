<?php

namespace App\Services\Contracts;

use App\Models\Game;
use App\DTO\ValidatedGameData;

interface GameCommandInterface
{
    public function create(ValidatedGameData $data): Game;
    public function update(Game $game, ValidatedGameData $data): void;
    public function delete(Game $game): void;
}