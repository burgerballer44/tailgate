<?php

namespace App\Http\Requests\User;

use App\Models\UserRole;
use App\Models\UserStatus;
use App\DTO\ValidatedUserData;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Http\Requests\FormRequest;

class UpdateUserRequest  extends FormRequest
{
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = request()->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'status' => ['required', new Enum(UserStatus::class)],
            'role' => ['required', new Enum(UserRole::class)],
        ];
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
