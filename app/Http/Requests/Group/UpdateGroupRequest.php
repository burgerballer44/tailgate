<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;

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
     * Allows admins to update group name and limit settings.
     * The owner can only be changed by developers using a separate endpoint.
     *
     * @return array<string, mixed> The group field validation rules.
     */
    public function rules(): array
    {
        return $this->developerUpdateRules();
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
