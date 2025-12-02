<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;

class UpdatePlayerRequest extends FormRequest
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

    /**
     * Get the validated data as a ValidatedPlayerData object.
     * This method is used to pass validated player data to the service layer.
     *
     * @return ValidatedPlayerData The validated player data transfer object.
     */
    public function toDTO(): ValidatedPlayerData
    {
        return ValidatedPlayerData::fromArray($this->validated());
    }
}
