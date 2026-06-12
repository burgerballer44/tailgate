<?php

namespace App\Http\Requests\Traits;

use App\Models\Sport;
use Illuminate\Validation\Rule;

trait FollowValidationRulesTrait
{
    /**
     * Defines shared validation rules for  fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'team_id' => ['required', 'exists:teams,id'],
            'sport' => [
                'nullable',
                Rule::enum(Sport::class),
                Rule::exists('team_sports', 'sport')->where(function ($query) {
                    $query->where('team_id', $this->input('team_id'));
                }),
            ],
        ];
    }

    /**
     * Defines validation rules used when creating a .
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Defines validation rules used when updating a .
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
