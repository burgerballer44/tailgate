<?php

namespace App\Http\Requests\Team;

use App\DTO\ValidatedTeamData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\TeamValidationRulesTrait;

class UpdateTeamRequest extends FormRequest
{
    use TeamValidationRulesTrait;

    protected function prepareForValidation(): void
    {
        $this->prepareTeamJsonFields();
    }

    /**
     * Authorizes this request in the current application context.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Defines validation rules for this request payload.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->updateRules();
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated team data to the service layer.
     *
     * @return ValidatedTeamData The validated team data transfer object.
     */
    public function toDTO(): ValidatedTeamData
    {
        return ValidatedTeamData::fromArray($this->validated());
    }
}
