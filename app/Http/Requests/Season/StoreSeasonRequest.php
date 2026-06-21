<?php

namespace App\Http\Requests\Season;

use App\DTO\ValidatedSeasonData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\SeasonValidationRulesTrait;

class StoreSeasonRequest extends FormRequest
{
    use SeasonValidationRulesTrait;

    /**
     * Prepare the data for validation by converting the active field to a boolean.
     *
     * This ensures that the checkbox field is properly converted to a boolean value before
     * validation runs, allowing the validation rules to expect a boolean type.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * Authorize administrators to create a season.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can create new seasons.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the season creation request data.
     *
     * Ensures the season name is provided, sport and season type are valid enum values,
     * and the active flag is a boolean.
     *
     * @return array<string, mixed> The season field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
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
