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
     * Authorize administrators to update a user.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can modify user account information.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the user update request data.
     *
     * Ensures the updated email is unique except for the current user, and status and role
     * enums are valid. Retrieves the user from the route parameter to validate against
     * the correct user record.
     *
     * @return array<string, ValidationRule|array|string> The user field validation rules.
     */
    public function rules(): array
    {
        $user = request()->route('user');

        return $this->updateRules($user);
    }

    /**
     * Transform validated user data into a data transfer object for the service layer.
     *
     * @return ValidatedUserData The validated user data transfer object.
     */
    public function toDTO(): ValidatedUserData
    {
        return ValidatedUserData::fromArray($this->validated());
    }
}
