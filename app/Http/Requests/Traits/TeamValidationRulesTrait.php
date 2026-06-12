<?php

namespace App\Http\Requests\Traits;

use App\Models\Sport;
use App\Models\TeamType;
use Illuminate\Validation\Rules\Enum;

trait TeamValidationRulesTrait
{
    /**
     * Defines shared validation rules for  fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'organization' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'conference' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:32'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logos' => ['nullable', 'array'],
            'logos.*' => ['string', 'url', 'max:2048'],
            'social_media' => ['nullable', 'array'],
            'social_media.*' => ['array:label,url'],
            'social_media.*.label' => ['required', 'string', 'max:255'],
            'social_media.*.url' => ['required', 'string', 'url', 'max:2048'],
            'type' => ['required', new Enum(TeamType::class)],
            'sports' => ['required', 'array', 'min:1'],
            'sports.*' => [new Enum(Sport::class)],
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

    /**
     * Decode JSON fields submitted as strings into arrays for validation.
     */
    protected function prepareTeamJsonFields(): void
    {
        $this->decodeJsonArrayField('logos');
        $this->decodeJsonArrayField('social_media');
    }

    private function decodeJsonArrayField(string $field): void
    {
        $value = $this->input($field);

        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $this->merge([$field => $decoded]);
        }
    }
}
