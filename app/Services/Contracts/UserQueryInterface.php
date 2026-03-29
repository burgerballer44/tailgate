<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface UserQueryInterface
{
    public function query(array $query): Builder;
    public function getAccessibleGroups(User $user): Collection;
}