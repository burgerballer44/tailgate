<?php

namespace App\Http\Requests\Traits;

use App\Rules\PlayerLimit;
use App\Rules\UniqueUsernamePerGroup;
use Illuminate\Contracts\Validation\ValidationRule;

trait PlayerValidationRulesTrait
{
    /**
     * Define base validation rules for player data.
     *
     * Validates that the player has a non-empty name as a string.
     *
     * @return array<string, ValidationRule|array|string> The base player field validation rules.
     */
    protected function baseRules(): array
    {
        return [
            'player_name' => ['required', 'string'],
        ];
    }

    /**
     * Define validation rules for creating a player in a group.
     *
     * Ensures the player name is unique within the group and the group has not exceeded its
     * player limit.
     *
     * @return array<string, ValidationRule|array|string> The player creation validation rules.
     */
    protected function storeRules(): array
    {
        return [
            'player_name' => ['required', 'string', new PlayerLimit, new UniqueUsernamePerGroup],
        ];
    }

    /**
     * Define validation rules for updating a player.
     *
     * Allows changing the player name and optionally reassigning the player to a different member.
     *
     * @return array<string, ValidationRule|array|string> The player update validation rules.
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'member_id' => ['nullable', 'exists:members,id'],
        ]);
    }
}
