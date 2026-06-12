<?php

namespace App\Services\Contracts;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Retrieves season information and related game data, and discovers available seasons.
 * Provides season lookups with eager-loaded games and teams, and lists active seasons for following workflows,
 * supporting season browsing and game management features.
 */
interface SeasonQueryInterface
{
    public function query(array $query): Builder;

    public function loadWithGames(Season $season): Season;

    public function getAvailableSeasonsForFollow(): Collection;
}
