<?php

namespace App\Services\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;

interface MemberQueryInterface
{
    public function query(array $query): Builder;
}
