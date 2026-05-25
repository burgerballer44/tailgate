<?php

namespace App\Services\Contracts;

use App\Models\Member;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface PlayerQueryInterface
{
    public function query(array $query): Builder;

    public function getAllForMember(Member $member): Collection;
}
