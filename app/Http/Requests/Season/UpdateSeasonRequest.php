<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedSeasonData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\SeasonValidationRulesTrait;

class UpdateSeasonRequest extends FormRequest
{
    use SeasonValidationRulesTrait;

    /**
     * Prepare the data for validation by converting the active field to a boolean.
     *
     * This ensures that the checkbox field is properly converted to a boolean value before
     * validation runs, allowing the validation rules to expect a boolean type.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * Authorize administrators to update a season.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can modify season configuration.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the season update request data.
     *
     * Ensures the updated season name, sport, season type, and active flag conform to season requirements.
     *
     * @return array<string, mixed> The season field validation rules.
     */
    public function rules(): array
    {
        return $this->updateRules();
    }

    /**
     * Transform validated season data into a data transfer object for the service layer.
     *
     * @return ValidatedSeasonData The validated season data transfer object.
     */
    public function toDTO(): ValidatedSeasonData
    {
        return ValidatedSeasonData::fromArray($this->validated());
    }
}
