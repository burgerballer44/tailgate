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
     * @param  array<string, mixed>  $data  All validated request data.
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validates that the referenced user currently belongs to the group.
     *
     * Falls back to 'owner_id' when 'user_id' is absent, which supports both
     * member-centric and owner-centric request shapes without duplicating the rule.
     *
     * @param  string  $attribute  The dot-notation field name being validated.
     * @param  mixed  $value  The value under validation.
     * @param  Closure(string): void  $fail  Closure invoked with an error message if validation fails.
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
