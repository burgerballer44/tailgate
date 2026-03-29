<?php

namespace App\Http\Requests\Traits;

use App\Rules\PlayerLimit;
use App\Rules\UniqueUsernamePerGroup;
use Illuminate\Contracts\Validation\ValidationRule;

trait PlayerValidationRulesTrait
{
    /**
     * Get the base validation rules for player fields.
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
     * Get the validation rules for storing a player.
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
     * Get the validation rules for updating a player.
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
