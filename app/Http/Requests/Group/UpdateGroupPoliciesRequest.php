<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupPoliciesData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;

class UpdateGroupPoliciesRequest extends FormRequest
{
    use GroupValidationRulesTrait;

    /**
     * Authorize authenticated users to update group policies.
     *
     * Group admin authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate optional group-level prediction policy selections.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'enabled_prediction_policies' => ['sometimes', 'array'],
            'enabled_prediction_policies.*' => ['string', 'distinct', 'in:'.implode(',', $this->groupPolicyKeys())],
        ];
    }

    /**
     * Convert validated input to a dedicated policy DTO.
     *
     * The dedicated policies endpoint treats missing checkbox input as an
     * intentional "clear all" action.
     */
    public function toDTO(): ValidatedGroupPoliciesData
    {
        return ValidatedGroupPoliciesData::fromArray([
            'enabled_prediction_policies' => $this->input('enabled_prediction_policies', []),
        ]);
    }
}