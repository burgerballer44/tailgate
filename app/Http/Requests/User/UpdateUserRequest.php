<?php

namespace App\Http\Requests\User;

use App\DTO\ValidatedUserData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\UserValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateUserRequest extends FormRequest
{
    use UserValidationRulesTrait;

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
        $user = request()->route('user');

        return $this->updateRules($user);
    }

    /**
     * Maps validated input into a  DTO.
     * This method is used to pass validated user data to the service layer.
     *
     * @return ValidatedUserData The validated user data transfer object.
     */
    public function toDTO(): ValidatedUserData
    {
        return ValidatedUserData::fromArray($this->validated());
    }
}
