<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedFollowData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\FollowValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class FollowTeamRequest extends FormRequest
{
    use FollowValidationRulesTrait;

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
        return $this->storeRules();
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated follow data to the service layer.
     *
     * @return ValidatedFollowData The validated follow data transfer object.
     */
    public function toDTO(): ValidatedFollowData
    {
        return ValidatedFollowData::fromArray($this->validated());
    }
}
