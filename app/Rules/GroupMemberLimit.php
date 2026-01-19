<?php

namespace App\Rules;

use App\Services\GroupService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GroupMemberLimit implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        if (GroupService::isGroupMemberLimitReached($group)) {
            $fail('Group member limit reached.');
        }
    }
}
