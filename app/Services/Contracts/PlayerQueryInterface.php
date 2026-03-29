<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface PlayerQueryInterface
{
    public function query(array $query): Builder;
}