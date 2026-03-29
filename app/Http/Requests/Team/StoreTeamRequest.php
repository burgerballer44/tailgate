<?php

namespace App\Http\Requests\Team;

use App\DTO\ValidatedTeamData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\TeamValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreTeamRequest extends FormRequest
{
    use TeamValidationRulesTrait;

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
     * Get the validated data as a ValidatedTeamData object.
     * This method is used to pass validated team data to the service layer.
     *
     * @return ValidatedTeamData The validated team data transfer object.
     */
    public function toDTO(): ValidatedTeamData
    {
        return ValidatedTeamData::fromArray($this->validated());
    }
}
