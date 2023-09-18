<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\ApiFormRequest;
use App\Rules\MustNotBeAMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends ApiFormRequest
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
            'group_id' => ['required', 'exists:groups,id'],
            'user_id' => [
                'required',
                'exists:users,id',
                new MustNotBeAMember
            ],
        ];
    }
}
