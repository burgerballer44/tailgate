<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiFormRequest;
use App\Models\Sport;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends ApiFormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'status' => ['required', new Enum(UserStatus::class)],
            'role' => ['required', new Enum(UserRole::class)],
        ];
    }
}
