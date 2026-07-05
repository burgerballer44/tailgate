<?php

namespace App\Http\Requests\Traits;

use App\Models\Sport;
use App\Models\TeamType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

trait TeamValidationRulesTrait
{
    /**
     * Define base validation rules for team data.
     *
     * Validates all team fields including organization, designation, a default conference value,
     * logos, social media links, team type, and supported sports. Color must be a valid hex code
     * if provided.
     *
     * @return array<string, ValidationRule|array|string> The base team field validation rules.
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
     * Define validation rules for creating a team.
     *
     * @return array<string, ValidationRule|array|string> The team creation validation rules.
     */
    protected function storeRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Define validation rules for updating a team.
     *
     * @return array<string, ValidationRule|array|string> The team update validation rules.
     */
    protected function updateRules(): array
    {
        return $this->baseRules();
    }

    /**
     * Decode JSON string fields into arrays for validation and storage.
     *
     * Form submissions may serialize complex fields as JSON strings. This method converts them
     * back to arrays so validation rules can properly validate array structure.
     */
    protected function prepareTeamJsonFields(): void
    {
        $this->decodeJsonArrayField('logos');
        $this->decodeJsonArrayField('social_media');
    }

    /**
     * Decode a JSON field if it is a string, merging the result back into request data.
     *
     * This helper is used to convert JSON-encoded form fields into PHP arrays. It silently
     * ignores invalid JSON or non-string values, leaving them unchanged for validation to handle.
     *
     * @param  string  $field  The field name to decode.
     */
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
