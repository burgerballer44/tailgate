<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedMemberData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\MemberValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreMemberRequest extends FormRequest
{
    use MemberValidationRulesTrait;

    /**
     * Authorize group administrators to add members to a group.
     *
     * Authorization is checked at the controller or policy level to ensure only group admins
     * can invite or add new members to the group.
     *
     * @return bool Always true; group admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the group member creation request data.
     *
     * Ensures the user exists, is not already a member of the group, and the group can accept
     * additional members. Also validates the role and status assignments.
     *
     * @return array<string, ValidationRule|array|string> The member field validation rules.
     */
    public function rules(): array
    {
        return $this->createMemberRules();
    }

    /**
     * Transform validated member data into a data transfer object for the service layer.
     *
     * @return ValidatedMemberData The validated member data transfer object.
     */
    public function toDTO(): ValidatedMemberData
    {
        return ValidatedMemberData::fromArray($this->validated());
    }
}
