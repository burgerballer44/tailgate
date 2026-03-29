<?php

namespace App\Http\Requests\Traits;

use Illuminate\Contracts\Validation\ValidationRule;

trait GameValidationRulesTrait
{
    /**
     * Get the base validation rules for game fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'season_id' => ['required', 'exists:seasons,id'],
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id', 'different:home_team_id'],
            'home_team_score' => ['required', 'integer', 'min:0'],
            'away_team_score' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validation rules for storing a game.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Get the validation rules for updating a game.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
