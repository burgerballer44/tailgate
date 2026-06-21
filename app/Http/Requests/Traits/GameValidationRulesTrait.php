<?php

namespace App\Http\Requests\Traits;

trait GameValidationRulesTrait
{
    /**
     * Define base validation rules for game data.
     *
     * Ensures both teams exist and are different, scores are non-negative, start date is properly
     * formatted, and the start_time_tbd flag is a boolean. These rules apply to both creation and updates.
     *
     * @return array<string, mixed> The game field validation rules.
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
     * Define validation rules for creating a game.
     *
     * @return array<string, mixed> The game field validation rules.
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Define validation rules for updating a game.
     *
     * @return array<string, mixed> The game field validation rules.
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
