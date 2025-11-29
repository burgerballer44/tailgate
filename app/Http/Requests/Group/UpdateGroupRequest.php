<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\FormRequest;
use App\Rules\UserMustBeAMember;

class UpdateGroupRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'member_limit' => ['nullable', 'integer', 'max:50'],
            'player_limit' => ['nullable', 'integer', 'max:10'],
            'owner_id' => ['nullable', new UserMustBeAMember],
        ];
    }
}
