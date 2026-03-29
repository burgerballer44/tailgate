<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface TeamQueryInterface
{
    public function query(array $query): Builder;
}