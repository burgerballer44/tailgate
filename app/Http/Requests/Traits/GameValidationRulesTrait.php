<?php

namespace App\Http\Requests\Traits;

trait GameValidationRulesTrait
{
    /**
     * Defines shared validation rules for  fields.
     *
     * @return array<string, mixed>
     */
    protected function baseRules(): array
    {
        return [
            'season_id' => ['required', 'exists:seasons,id'],
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id', 'different:home_team_id'],
            'home_team_score' => ['required', 'integer', 'min:0'],
            'away_team_score' => ['required', 'integer', 'min:0'],
            'start_date_time' => ['nullable', 'date'],
            'start_time_tbd' => ['boolean'],
        ];
    }

    /**
     * Defines validation rules used when creating a .
     *
     * @return array<string, mixed>
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Defines validation rules used when updating a .
     *
     * @return array<string, mixed>
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
