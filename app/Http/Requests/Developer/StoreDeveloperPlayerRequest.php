<?php

namespace App\Http\Requests\Developer;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;

class StoreDeveloperPlayerRequest extends FormRequest
{
    /**
     * Authorize the developer to create player records programmatically.
     *
     * This endpoint is available to developers for testing and integration purposes. Authorization
     * should be enforced at the controller level to ensure only authorized developers can access this functionality.
     *
     * @return bool Always true; controller-level authorization is required.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the player name field required for developer player creation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string> The player_name field validation rules.
     */
    public function rules(): array
    {
        return [
            'player_name' => ['required', 'string'],
        ];
    }

    /**
     * Transform validated player data into a data transfer object for the service layer.
     *
     * @return ValidatedPlayerData The validated player data transfer object.
     */
    public function toDTO(): ValidatedPlayerData
    {
        return ValidatedPlayerData::fromArray($this->validated());
    }
}
