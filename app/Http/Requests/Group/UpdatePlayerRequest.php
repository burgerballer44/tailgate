<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\PlayerValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePlayerRequest extends FormRequest
{
    use PlayerValidationRulesTrait;

    /**
     * Authorize group members to update player information.
     *
     * Authorization is checked at the controller or policy level to ensure the user has permission
     * to modify the player within the group context.
     *
     * @return bool Always true; group-level authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the group player update request data.
     *
     * Allows updating the player name and optionally reassigning the player to a different member.
     * Updated player names must still be unique within the group.
     *
     * @return array<string, ValidationRule|array|string> The player field validation rules.
     */
    public function rules(): array
    {
        return $this->updateRules();
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
