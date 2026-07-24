<?php

namespace App\Http\Requests\Traits;

use App\Models\User;
use App\Models\Enums\UserRole;
use App\Models\Enums\UserStatus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

trait UserValidationRulesTrait
{
    /**
     * Define base validation rules for user data.
     *
     * Validates that the user has a name, unique email, and valid status and role enum values.
     *
     * @return array<string, ValidationRule|array|string> The base user field validation rules.
     */
    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'status' => ['required', new Enum(UserStatus::class)],
            'role' => ['required', new Enum(UserRole::class)],
        ];
    }

    /**
     * Define validation rules for creating a user.
     *
     * Requires a unique email address that does not already exist in the system.
     *
     * @return array<string, ValidationRule|array|string> The user creation validation rules.
     */
    protected function storeRules(): array
    {
        return array_merge($this->baseRules(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
        ]);
    }

    /**
     * Define validation rules for updating a user.
     *
     * Allows email to be updated while remaining unique, except for the user's current email address.
     *
     * @param  User  $user  The user being updated; used to exclude their current email from uniqueness check.
     * @return array<string, ValidationRule|array|string> The user update validation rules.
     */
    protected function updateRules(User $user): array
    {
        return array_merge($this->baseRules(), [
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ]);
    }
}
