<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedMemberData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\MemberValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateMemberRequest extends FormRequest
{
    use MemberValidationRulesTrait;

    /**
     * Authorize group administrators to update member roles and status.
     *
     * Authorization is checked at the controller or policy level to ensure only group admins
     * can modify member roles and status within the group.
     *
     * @return bool Always true; group admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the group member update request data.
     *
     * Allows updating a member's role and status. Role changes enforce a minimum admin requirement
     * to prevent removing all admins from the group.
     *
     * @return array<string, ValidationRule|array|string> The member field validation rules.
     */
    public function rules(): array
    {
        return $this->updateMemberRules();
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
