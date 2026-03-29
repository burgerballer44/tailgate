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
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return $this->storeRules();
    }

    /**
     * Get the validated data as a ValidatedFollowData object.
     * This method is used to pass validated follow data to the service layer.
     *
     * @return ValidatedFollowData The validated follow data transfer object.
     */
    public function toDTO(): ValidatedFollowData
    {
        return ValidatedFollowData::fromArray($this->validated());
    }
}
