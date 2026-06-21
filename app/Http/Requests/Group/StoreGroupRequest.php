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
     * Authorize any authenticated user to create a group.
     *
     * Any authenticated user may create a group and will automatically become its owner.
     * Authorization is based on authentication status alone; group ownership is enforced by
     * automatically setting the owner_id to the authenticated user.
     *
     * @return bool Always true for authenticated users.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Provide human-friendly attribute labels for validation error messages.
     *
     * @return array<string, string> Display names for the name and owner_id fields.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Group Name',
            'owner_id' => 'Owner ID',
        ];
    }

    /**
     * Prepare the data for validation by automatically setting the group owner.
     *
     * If no owner_id is provided in the request, automatically set it to the currently authenticated
     * user's ID. This ensures that groups are always created with the correct ownership without
     * requiring the client to pass the owner_id explicitly.
     *
     * @return void
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
     * Validate the group creation request data.
     *
     * Enforces that the group has a name and a valid owner (user).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string> The group field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
    }

    /**
     * Transform validated group data into a data transfer object for the service layer.
     *
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(): ValidatedGroupData
    {
        return ValidatedGroupData::fromArray($this->validated());
    }
}
