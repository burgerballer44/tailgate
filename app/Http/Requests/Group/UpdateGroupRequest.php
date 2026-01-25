<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;

class UpdateGroupRequest extends FormRequest
{
    use GroupValidationRulesTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->developerUpdateRules();
    }

    /**
     * Get the validated data as a ValidatedGroupData object.
     * This method is used to pass validated group data to the service layer.
     *
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(): ValidatedGroupData
    {
        return ValidatedGroupData::fromArray($this->validated());
    }
}
