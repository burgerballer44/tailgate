<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateGroupRequest extends FormRequest
{
    use GroupValidationRulesTrait;

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
        return $this->developerUpdateRules();
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated group data to the service layer.
     *
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(): ValidatedGroupData
    {
        return ValidatedGroupData::fromArray($this->validated());
    }
}
