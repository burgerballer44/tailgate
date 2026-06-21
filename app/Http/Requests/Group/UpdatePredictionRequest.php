<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPredictionData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\PredictionValidationRulesTrait;

class UpdatePredictionRequest extends FormRequest
{
    use PredictionValidationRulesTrait;

    /**
     * Authorize group members to update their prediction.
     *
     * Authorization is checked at the controller or policy level to ensure the user can only
     * update predictions for their own player in the group.
     *
     * @return bool Always true; group-level authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the prediction update request data.
     *
     * Ensures both team predictions are non-negative integers. The prediction_id must be provided
     * to identify which prediction is being updated.
     *
     * @return array<string, array|string> The prediction field validation rules.
     */
    public function rules(): array
    {
        return $this->updateRules();
    }

    /**
     * Transform validated prediction data into a data transfer object for the service layer.
     *
     * @return ValidatedPredictionData The validated prediction data transfer object.
     */
    public function toDTO(): ValidatedPredictionData
    {
        return ValidatedPredictionData::fromArray($this->validated());
    }
}