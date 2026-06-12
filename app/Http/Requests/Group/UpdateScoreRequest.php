<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedScoreData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\ScoreValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateScoreRequest extends FormRequest
{
    use ScoreValidationRulesTrait;

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
     * This method is used to pass validated score data to the service layer.
     *
     * @return ValidatedScoreData The validated score data transfer object.
     */
    public function toDTO(): ValidatedScoreData
    {
        return ValidatedScoreData::fromArray($this->validated());
    }
}
