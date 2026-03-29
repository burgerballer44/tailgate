<?php

namespace App\Services\Contracts;

use App\Models\Season;
use Illuminate\Contracts\Database\Eloquent\Builder;

interface GameQueryInterface
{
    public function query(array $filters): Builder;
    public function getAvailableTeamsForSeason(Season $season): array;
}