<?php

namespace App\Http\Requests\Traits;

use App\Models\Sport;
use Illuminate\Validation\Rule;

trait FollowValidationRulesTrait
{
    /**
     * Get the base validation rules for follow fields.
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
     * Get the validation rules for storing a follow.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Get the validation rules for updating a follow.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }
}
