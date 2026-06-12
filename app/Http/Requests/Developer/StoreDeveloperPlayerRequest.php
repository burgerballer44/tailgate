<?php

namespace App\Http\Requests\Developer;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;

class StoreDeveloperPlayerRequest extends FormRequest
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
            'player_name' => ['required', 'string'],
        ];
    }

    /**
     * Maps validated input into a  DTO.
     */
    public function toDTO(): ValidatedPlayerData
    {
        return ValidatedPlayerData::fromArray($this->validated());
    }
}
