<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\Enums\UserRole;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Authorize the current user to update their profile.
     *
     * Any authenticated user may update their own profile information. Authorization is enforced
     * at the controller level by ensuring the requested user matches the authenticated user.
     *
     * @return bool Always true since the request is only available to authenticated users.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the editable profile attributes for the current user.
     *
     * Defines the validation rules for user profile updates including name, email, and role.
     * Email must be unique across the application except for the current user's existing email.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string> The profile fields accepted by the edit form.
     */
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'role' => [new Enum(UserRole::class)],
        ];
    }
}
