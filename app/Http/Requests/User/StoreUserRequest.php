<?php

namespace App\Http\Requests\User;

use App\DTO\ValidatedUserData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\UserValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreUserRequest extends FormRequest
{
    use UserValidationRulesTrait;

    /**
     * Authorize administrators to create a new user.
     *
     * Authorization is checked at the controller or policy level to ensure only administrators
     * can create new user accounts.
     *
     * @return bool Always true; admin authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the user creation request data.
     *
     * Ensures the user has a unique email, valid status and role enums, and all required fields.
     * Email uniqueness prevents duplicate accounts in the system.
     *
     * @return array<string, ValidationRule|array|string> The user field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
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
