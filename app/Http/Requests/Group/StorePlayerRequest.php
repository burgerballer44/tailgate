<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\PlayerValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class StorePlayerRequest extends FormRequest
{
    use PlayerValidationRulesTrait;

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
     * This method is used to pass validated player data to the service layer.
     *
     * @return ValidatedPlayerData The validated player data transfer object.
     */
    public function toDTO(): ValidatedPlayerData
    {
        return ValidatedPlayerData::fromArray($this->validated());
    }
}
