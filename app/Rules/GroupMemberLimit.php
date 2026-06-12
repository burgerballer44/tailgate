<?php

namespace App\Rules;

use App\Services\Contracts\GroupQueryInterface;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces configured group member capacity before allowing new memberships.
 * Prevents over-allocation of groups that have reached their limit.
 */
class GroupMemberLimit implements ValidationRule
{
    /**
     * Validates that the group can accept another member under its configured capacity.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        if (app(GroupQueryInterface::class)->isGroupMemberLimitReached($group)) {
            $fail('Group member limit reached.');
        }
    }
}
