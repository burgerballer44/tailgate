<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Services\Contracts\MemberQueryInterface;

class MemberQueryService implements MemberQueryInterface
{
    /**
     * Filter members based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param array $query An associative array of query parameters to filter members.
     * @return Builder A query builder instance for the filtered members.
     */
    public function query(array $query): Builder
    {
        $builder = Member::query();

        if (isset($query['user_id'])) {
            $builder->where('user_id', $query['user_id']);
        }

        if (isset($query['group_id'])) {
            $builder->where('group_id', $query['group_id']);
        }

        if (isset($query['status'])) {
            $builder->where('status', $query['status']);
        }

        return $builder;
    }
}