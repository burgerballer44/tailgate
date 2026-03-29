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
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = request()->route('user');

        return $this->updateRules($user);
    }

    /**
     * Get the validated data as a ValidatedUserData object.
     * This method is used to pass validated user data to the service layer.
     *
     * @return ValidatedUserData The validated user data transfer object.
     */
    public function toDTO(): ValidatedUserData
    {
        return ValidatedUserData::fromArray($this->validated());
    }
}
