<?php
 
namespace App\Rules;
 
use App\Models\Group;
use App\Models\GroupRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
 
class GroupAdminMinimum implements ValidationRule
{

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');
        $member = request()->route('member');

        // if the minimum number of admins has been reached AND
        // the member being updated is the only admin AND
        // the role is being updated to something els
        if (
            Group::MIN_NUMBER_ADMINS == $group->admin->count() &&
            $group->admin->first() == $member &&
            GroupRole::GROUP_ADMIN != $value
        ) {
            $fail('Group admin minimum reached. Please update a different member to the Group Admin role before updating this member.');
        }

    }
}