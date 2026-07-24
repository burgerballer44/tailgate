<?php

namespace App\Rules;

use App\Models\Enums\InitialGroupLimitRule;
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
     *
     * Admins managing players via the manage route are subject to the group-level player
     * limit, while regular members are capped at the fixed per-member default. This dual
     * limit allows admins to provision more players than self-service members can.
     *
     * @param  string  $attribute  The dot-notation field name being validated.
     * @param  mixed  $value  The value under validation.
     * @param  Closure(string): void  $fail  Closure invoked with an error message if validation fails.
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
            : InitialGroupLimitRule::MEMBER_PLAYER_LIMIT->value();

        if ($member->players()->count() >= $limit) {
            $fail('Player limit reached.');
        }
    }
}
