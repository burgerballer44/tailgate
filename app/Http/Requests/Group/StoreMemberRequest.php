<?php

namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\GroupMemberLimit;
use App\Rules\MustNotBeAMember;

class StoreMemberRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id', new MustNotBeAMember, new GroupMemberLimit],
        ];
    }
}
