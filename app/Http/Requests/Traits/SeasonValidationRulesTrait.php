<?php

namespace App\Http\Requests\Traits;

use App\Models\SeasonType;
use App\Models\Sport;
use Illuminate\Validation\Rules\Enum;

trait SeasonValidationRulesTrait
{
    /**
     * Get the base validation rules for season fields.
     *
    * @return array<string, mixed>
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
     * Get the validation rules for storing a season.
     *
    * @return array<string, mixed>
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'active' => ['boolean'],
        ]);
    }

    /**
     * Get the validation rules for updating a season.
     *
    * @return array<string, mixed>
     */
    protected function updateRules(): array
    {
        return array_merge($this->baseRules(), [
            'active' => ['boolean'],
        ]);
    }
}
