<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class JoinGroupRequest extends FormRequest
{
    /**
     * Authorize any authenticated user to join a group using an invite code.
     *
     * Any authenticated user may submit a group join request. The actual authorization
     * is enforced by validating the invite code, ensuring it exists and is still valid.
     *
     * @return bool Always true; validation of the invite code provides the actual authorization.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the group join request with invite code.
     *
     * The invite code is required as a string. Validation at the service layer will verify
     * that the code is valid and not expired.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string> The invite_code validation rules.
     */
    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string'],
        ];
    }
}
