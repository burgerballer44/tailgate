<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\ApiFormRequest;

class UpdatePlayerRequest extends ApiFormRequest
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
            'member_id' => ['nullable', 'exists:members,id'],
        ];
    }
}
