<?php

namespace App\Rules;

use App\Services\Contracts\GroupQueryInterface;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks add-member flows when the target user already belongs to the group.
 * Protects membership integrity by preventing duplicate member records.
 */
class MustNotBeAMember implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Captures request payload values needed for cross-field membership checks.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Validates that the target user is not already a member of the current group.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $group = request()->route('group');

        if (app(GroupQueryInterface::class)->isUserAlreadyMember($group, $this->data['user_id'])) {
            $fail('The user is already a member of the group.');
        }
    }
}
