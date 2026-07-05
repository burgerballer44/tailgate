<?php

namespace App\Http\Requests\Team;

use App\DTO\ValidatedTeamData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\TeamValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateTeamRequest extends FormRequest
{
    use TeamValidationRulesTrait;

    /**
     * Prepare the data for validation by decoding JSON fields.
     *
     * Converts JSON string representations of logos and social media arrays into PHP arrays
     * to allow validation against array rules. This is necessary because form submissions may
     * serialize these complex fields as JSON strings.
     */
    protected function prepareForValidation(): void
    {
        $this->prepareTeamJsonFields();
    }

    /**
     * Authorize administrators to update a team.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can modify team information.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the team update request data.
     *
     * Ensures the updated team fields conform to team requirements, including identity fields,
     * a default conference for selected sports, logos, social media links, team type, and
     * supported sports.
     *
     * @return array<string, ValidationRule|array|string> The team field validation rules.
     */
    public function rules(): array
    {
        return $this->updateRules();
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
