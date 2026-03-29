<?php

namespace App\Services\Contracts;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;

interface SeasonQueryInterface
{
    public function query(array $query): Builder;
    public function loadWithGames(Season $season): Season;
}