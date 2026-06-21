<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPredictionData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\PredictionValidationRulesTrait;

class SubmitPredictionRequest extends FormRequest
{
    use PredictionValidationRulesTrait;

    /**
     * Authorize group members to submit a prediction.
     *
     * Authorization is checked at the controller or policy level to ensure the user is a member
     * of the group and the prediction belongs to their player in that group.
     *
     * @return bool Always true; group-level authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the prediction submission request data.
     *
     * Ensures both team predictions are non-negative integers, the game belongs to a team the user
     * is following, and no prediction has already been submitted for this game by this player.
     *
     * @return array<string, array|string> The prediction field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
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