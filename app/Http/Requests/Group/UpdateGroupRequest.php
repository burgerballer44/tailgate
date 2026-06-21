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
     * Authorize group administrators to update group settings.
     *
     * Authorization is checked at the controller or policy level to ensure only group admins
     * can modify group configuration and prediction policies.
     *
     * @return bool Always true; group admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the group update request data.
     *
     * Allows admins to update group name, member and player limits, and enabled prediction policies.
     * The owner can only be changed by developers using a separate endpoint.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string> The group field validation rules.
     */
    public function rules(): array
    {
        return array_merge($this->developerUpdateRules(), [
            'enabled_prediction_policies' => ['sometimes', 'array'],
            'enabled_prediction_policies.*' => ['string', 'distinct', 'in:'.implode(',', $this->groupPolicyKeys())],
        ]);
    }

    /**
     * Transform validated group data into a data transfer object for the service layer.
     *
     * Ensures enabled_prediction_policies is always an array, even if not provided in the request.
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
     * Get the list of valid group-level prediction policy keys.
     *
     * Retrieves policy keys from the PredictionPolicyEvaluator service to ensure validation
     * rules stay synchronized with available policies, preventing stale validation rules.
     *
     * @return array<int, string> List of enabled prediction policy keys.
     */
    private function groupPolicyKeys(): array
    {
        return array_values(array_map(
            static fn ($rule): string => $rule->key(),
            app(PredictionPolicyEvaluatorInterface::class)->groupRules(),
        ));
    }
}
