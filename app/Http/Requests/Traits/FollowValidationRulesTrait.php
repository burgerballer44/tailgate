<?php

namespace App\Http\Requests\Traits;

use App\Models\Sport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait FollowValidationRulesTrait
{
    /**
     * Define base validation rules for team follow data.
     *
     * Ensures the team exists and the specified sport is valid for that team if provided.
     *
     * @return array<string, ValidationRule|array|string> The team and sport validation rules.
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
     * Define validation rules for creating a follow relationship.
     *
     * @return array<string, ValidationRule|array|string> The team follow validation rules.
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Define validation rules for updating a follow relationship.
     *
     * @return array<string, ValidationRule|array|string> The team follow validation rules.
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
