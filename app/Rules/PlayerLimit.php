<?php

namespace App\Rules;

use App\Models\Group;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces per-member player capacity based on requester privileges.
 * Applies regular-member defaults or admin-managed group limits depending on route context.
 */
class PlayerLimit implements ValidationRule
{
    /**
     * Validates that the member can add another player in the current permission context.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');
        $member = request()->route('member');

        if (! $group || ! $member) {
            return;
        }

        $isManageRoute = request()->routeIs('groups.manage.members.players.*');
        $isAdminManageContext = $isManageRoute && $group->isAdminOrOwner(request()->user());

        $limit = $isAdminManageContext
            ? $group->player_limit
            : Group::REGULAR_MEMBER_PLAYER_LIMIT;

        if ($member->players()->count() >= $limit) {
            $fail('Player limit reached.');
        }
    }
}
