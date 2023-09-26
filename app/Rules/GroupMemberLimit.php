<?php
 
namespace App\Rules;
 
use App\Models\Group;
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

        if ($group->member_limit == $group->members->count()) {
            $fail('Group member limit reached.');
        }
    }
}