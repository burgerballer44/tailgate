<?php

namespace App\Http\Requests\Traits;

use App\Rules\UserMustBeAMember;
use Illuminate\Contracts\Validation\ValidationRule;

trait GroupValidationRulesTrait
{
    /**
     * Defines shared validation rules for  fields.
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
     * Defines admin-focused group update rules, including owner validation.
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
     * Defines validation rules used when creating a .
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
