<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;

class UserUpdateGroupRequest extends FormRequest
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
        return array_merge($this->baseRules(), [
            'name' => 'sometimes|required|string|max:255',
        ]);
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated group data to the service layer.
     *
     * @return ValidatedGroupData The validated group data transfer object.
     */
    public function toDTO(int $ownerId): ValidatedGroupData
    {
        $data = $this->validated();
        $data['owner_id'] = $ownerId;

        return ValidatedGroupData::fromArray($data);
    }
}
