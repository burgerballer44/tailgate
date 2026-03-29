<?php

namespace App\Http\Requests\Traits;

use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

trait SeasonValidationRulesTrait
{
    /**
     * Get the base validation rules for season fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sport' => ['required', new Enum(Sport::class)],
            'season_type' => ['required', new Enum(SeasonType::class)],
            'season_start' => ['required', 'date'],
            'season_end' => ['required', 'date', 'after:season_start'],
        ];
    }

    /**
     * Get the validation rules for storing a season.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'active' => ['required', 'boolean'],
            'active_date' => ['required', 'date'],
            'inactive_date' => ['required', 'date'],
        ]);
    }

    /**
     * Get the validation rules for updating a season.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'active' => ['nullable', 'boolean'],
            'active_date' => ['nullable', 'date'],
            'inactive_date' => ['nullable', 'date'],
        ]);
    }
}
