<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface TeamQueryInterface
{
    public function query(array $query): Builder;

    public function getAvailableTeamsForFollow(): Collection;
}
