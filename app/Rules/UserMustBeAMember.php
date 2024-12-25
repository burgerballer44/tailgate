<?php
 
namespace App\Rules;
 
use App\Models\Member;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
 
class UserMustBeAMember implements ValidationRule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;
    
        return $this;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        // could be passing in an owner_id or user_id?
        $idToUse = $this->data['owner_id'] ?? $this->data['user_id'];

        if (! $group->members->contains('user_id', $idToUse)) {
            $fail('The user is not a member of the group.');
        }
    }
}