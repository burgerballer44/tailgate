<?php

namespace App\Http\Requests\Traits;

use App\Rules\SeasonIsActive;

trait FollowValidationRulesTrait
{
    /**
     * Get the base validation rules for follow fields.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'team_id' => ['required', 'exists:teams,id'],
            'season_id' => ['required', 'exists:seasons,id', new SeasonIsActive],
        ];
    }

    /**
     * Get the validation rules for storing a follow.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Get the validation rules for updating a follow.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}