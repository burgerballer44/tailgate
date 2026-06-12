<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class JoinGroupRequest extends FormRequest
{
    /**
     * Authorizes this request in the current application context.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Defines validation rules for this request payload.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string'],
        ];
    }
}
