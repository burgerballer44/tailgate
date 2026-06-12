<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedMemberData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\MemberValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateMemberRequest extends FormRequest
{
    use MemberValidationRulesTrait;

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
        return $this->updateMemberRules();
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated member data to the service layer.
     *
     * @return ValidatedMemberData The validated member data transfer object.
     */
    public function toDTO(): ValidatedMemberData
    {
        return ValidatedMemberData::fromArray($this->validated());
    }
}
