<?php

namespace App\Http\Requests\Traits;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait UserValidationRulesTrait
{
    /**
     * Get the base validation rules for user fields.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'status' => ['required', new Enum(UserStatus::class)],
            'role' => ['required', new Enum(UserRole::class)],
        ];
    }

    /**
     * Get the validation rules for storing a user.
     *
     * @return array<string, ValidationRule|array|string>
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
        ]);
    }

    /**
     * Get the validation rules for updating a user.
     *
     * @param  User  $user  The user being updated
     * @return array<string, ValidationRule|array|string>
     */
    protected function updateRules(User $user): array
    {
        return array_merge($this->baseRules(), [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]);
    }
}
