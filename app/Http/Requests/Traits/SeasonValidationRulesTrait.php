<?php

namespace App\Http\Requests\Traits;

use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Validation\Rules\Enum;

trait SeasonValidationRulesTrait
{
    /**
     * Define base validation rules for season data.
     *
     * Validates that the season has a name and valid sport and season type enum values.
     *
     * @return array<string, mixed> The base season field validation rules.
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sport' => ['required', new Enum(Sport::class)],
            'season_type' => ['required', new Enum(SeasonType::class)],
        ];
    }

    /**
     * Define validation rules for creating a season.
     *
     * Requires a season name, sport, and season type. The active flag is a boolean.
     *
     * @return array<string, mixed> The season creation validation rules.
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'active' => ['boolean'],
        ]);
    }

    /**
     * Define validation rules for updating a season.
     *
     * Allows updating season name, sport, season type, and active status.
     *
     * @return array<string, mixed> The season update validation rules.
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'active' => ['boolean'],
        ]);
    }
}
