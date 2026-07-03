<?php

namespace App\Http\Requests\Team;

use App\DTO\ValidatedTeamData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\TeamValidationRulesTrait;

class StoreTeamRequest extends FormRequest
{
    use TeamValidationRulesTrait;

    /**
     * Prepare the data for validation by decoding JSON fields.
     *
     * Converts JSON string representations of logos and social media arrays into PHP arrays
     * to allow validation against array rules. This is necessary because form submissions may
     * serialize these complex fields as JSON strings.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->prepareTeamJsonFields();
    }

    /**
     * Authorize administrators to create a team.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can add new teams to the application.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the team creation request data.
     *
     * Ensures all required team fields are provided with valid values, including identity fields,
     * a default conference for selected sports, abbreviation, logos, social media, team type,
     * and supported sports. Optional fields like colors are also validated.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string> The team field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
    }

    /**
     * Transform validated team data into a data transfer object for the service layer.
     *
     * @return ValidatedTeamData The validated team data transfer object.
     */
    public function toDTO(): ValidatedTeamData
    {
        return ValidatedTeamData::fromArray($this->validated());
    }
}
