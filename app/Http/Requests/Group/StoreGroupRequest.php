<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreGroupRequest extends FormRequest
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
     * Defines human-friendly attribute labels for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Group Name',
            'owner_id' => 'Owner ID',
        ];
    }

    /**
     * Normalizes request data before validation runs.
     *
     * Automatically set the owner_id to the currently authenticated user's ID
     * if it's not already provided in the request. This ensures that groups
     * are always created with the correct ownership.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('owner_id')) {
            $this->merge([
                'owner_id' => $this->user()->id,
            ]);
        }
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
     * This method is used to pass validated group data to the service layer.
     *
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(): ValidatedGroupData
    {
        return ValidatedGroupData::fromArray($this->validated());
    }
}
