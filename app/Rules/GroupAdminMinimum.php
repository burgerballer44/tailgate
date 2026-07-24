<?php

namespace App\Rules;

use App\Models\Enums\GroupRole;
use App\Models\Enums\GroupThresholdRule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Preserves minimum administrative coverage for group governance.
 * Prevents demoting the final required admin without assigning a replacement first.
 */
class GroupAdminMinimum implements ValidationRule
{
    /**
     * Validates that admin role changes do not violate the minimum-admin requirement.
     *
     * A group must always have at least one admin. This prevents demoting the last
     * remaining admin without first promoting another member, which would leave the
     * group without governance.
     *
     * @param  string  $attribute  The dot-notation field name being validated.
     * @param  mixed  $value  The new role value being assigned.
     * @param  Closure(string): void  $fail  Closure invoked with an error message if validation fails.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');
        $member = request()->route('member');

        if (
            $group->admin->count() == GroupThresholdRule::MIN_NUMBER_ADMINS->value() &&
            $group->admin->first() == $member &&
            $value != GroupRole::GROUP_ADMIN
        ) {
            $fail('Group admin minimum reached. Please update a different member to the Group Admin role before updating this member.');
        }

    }
}
