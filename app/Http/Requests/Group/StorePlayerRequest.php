<?php

namespace App\Http\Requests\Group;

use App\Http\Requests\ApiFormRequest;
use App\Rules\PlayerLimit;
use App\Rules\UniqueUsernamePerGroup;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends ApiFormRequest
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
            'player_name' => ['required', 'string', new PlayerLimit, new UniqueUsernamePerGroup],
        ];
    }
}
