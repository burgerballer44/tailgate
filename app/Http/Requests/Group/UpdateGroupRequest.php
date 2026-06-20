<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;

class UpdateGroupRequest extends FormRequest
{
    use GroupValidationRulesTrait;

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
        return array_merge($this->developerUpdateRules(), [
            'enabled_prediction_policies' => ['sometimes', 'array'],
            'enabled_prediction_policies.*' => ['string', 'distinct', 'in:'.implode(',', $this->groupPolicyKeys())],
        ]);
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated group data to the service layer.
     *
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(): ValidatedGroupData
    {
        $validated = $this->validated();

        if (! array_key_exists('enabled_prediction_policies', $validated)) {
            $validated['enabled_prediction_policies'] = [];
        }

        return ValidatedGroupData::fromArray($validated);
    }

    /**
     * Gets the list of valid group-level prediction policy keys from the PredictionPolicyEvaluator service.
     * 
     * @return array<int, string>
     */
    private function groupPolicyKeys(): array
    {
        return array_values(array_map(
            static fn ($rule): string => $rule->key(),
            app(PredictionPolicyEvaluatorInterface::class)->groupRules(),
        ));
    }
}
