<?php

namespace App\Http\Requests\Traits;

use App\Rules\PlayerLimit;
use App\Rules\UniqueUsernamePerGroup;
use Illuminate\Contracts\Validation\ValidationRule;

trait PlayerValidationRulesTrait
{
    /**
     * Defines shared validation rules for  fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'player_name' => ['required', 'string'],
        ];
    }

    /**
     * Defines validation rules used when creating a .
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return [
            'player_name' => ['required', 'string', new PlayerLimit, new UniqueUsernamePerGroup],
        ];
    }

    /**
     * Defines validation rules used when updating a .
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'member_id' => ['nullable', 'exists:members,id'],
        ]);
    }
}
