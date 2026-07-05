<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UserUpdateGroupRequest extends FormRequest
{
    use GroupValidationRulesTrait;

    /**
     * Authorize authenticated users to update their group settings.
     *
     * Group owners may update group information they own. Authorization is checked at the
     * controller or policy level to ensure the user owns the group being modified.
     *
     * @return bool Always true; ownership authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the user-initiated group update request data.
     *
     * Allows group owners to update basic group information.
     * Sensitive fields like owner changes are not included.
     *
     * @return array<string, ValidationRule|array|string> The group field validation rules.
     */
    public function rules(): array
    {
        return array_merge($this->baseRules(), [
            'name' => 'sometimes|required|string|max:255',
        ]);
    }

    /**
     * Transform validated group data into a data transfer object for the service layer.
     *
     * Injects the group owner ID since the owner cannot be changed through this endpoint.
     *
     * @param  int  $ownerId  The ID of the group owner (typically the authenticated user).
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(int $ownerId): ValidatedGroupData
    {
        $data = $this->validated();
        $data['owner_id'] = $ownerId;

        return ValidatedGroupData::fromArray($data);
    }
}
