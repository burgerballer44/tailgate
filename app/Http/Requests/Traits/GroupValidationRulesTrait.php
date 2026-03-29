<?php

namespace App\Http\Requests\Traits;

use App\Rules\UserMustBeAMember;
use Illuminate\Contracts\Validation\ValidationRule;

trait GroupValidationRulesTrait
{
    /**
     * Get the base validation rules for group fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function baseRules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'member_limit' => ['nullable', 'integer', 'max:50'],
            'player_limit' => ['nullable', 'integer', 'max:10'],
        ];
    }

    /**
     * Get the validation rules for admin group updates (includes owner validation).
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function developerUpdateRules(): array
    {
        return array_merge($this->baseRules(), [
            'owner_id' => ['required', new UserMustBeAMember],
        ]);
    }

    /**
     * Get the validation rules for creating a group.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function storeRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'owner_id' => ['required', 'exists:users,id'],
        ];
    }
}
