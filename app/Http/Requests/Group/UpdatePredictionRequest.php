<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPredictionData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\PredictionValidationRulesTrait;

class UpdatePredictionRequest extends FormRequest
{
    use PredictionValidationRulesTrait;

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
    * @return array<string, array|string>
     */
    public function rules(): array
    {
        return $this->updateRules();
    }

    /**
     * Maps validated input into a DTO.
     * This method is used to pass validated prediction data to the service layer.
     *
     * @return ValidatedPredictionData The validated prediction data transfer object.
     */
    public function toDTO(): ValidatedPredictionData
    {
        return ValidatedPredictionData::fromArray($this->validated());
    }
}