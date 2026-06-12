<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Enforces that user-scoped group actions target an existing group member.
 * Supports owner and user identifiers from request data when resolving membership.
 */
class UserMustBeAMember implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Captures request payload values needed to resolve the user being validated.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validates that the referenced user currently belongs to the group.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        $idToUse = $this->data['owner_id'] ?? $this->data['user_id'];

        if (! $group->members->contains('user_id', $idToUse)) {
            $fail('The user is not a member of the group.');
        }
    }
}
