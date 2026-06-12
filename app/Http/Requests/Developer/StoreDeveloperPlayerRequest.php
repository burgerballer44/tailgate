<?php

namespace App\Http\Requests\Developer;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;

class StoreDeveloperPlayerRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'player_name' => ['required', 'string'],
        ];
    }

    /**
     * Get the validated data as a ValidatedPlayerData object.
     */
    public function toDTO(): ValidatedPlayerData
    {
        return ValidatedPlayerData::fromArray($this->validated());
    }
}
