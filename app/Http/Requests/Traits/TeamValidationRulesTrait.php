<?php

namespace App\Http\Requests\Traits;

use App\Models\Sport;
use App\Models\TeamType;
use Illuminate\Validation\Rules\Enum;

trait TeamValidationRulesTrait
{
    /**
     * Get the base validation rules for team fields.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'organization' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'mascot' => ['nullable', 'string', 'max:255'],
            'type' => ['required', new Enum(TeamType::class)],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => [new Enum(Sport::class)],
        ];
    }

    /**
     * Get the validation rules for storing a team.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Get the validation rules for updating a team.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}