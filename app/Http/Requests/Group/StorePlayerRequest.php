<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedPlayerData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\PlayerValidationRulesTrait;
use Illuminate\Contracts\Validation\ValidationRule;

class StorePlayerRequest extends FormRequest
{
    use PlayerValidationRulesTrait;

    /**
     * Authorize group members to create a player in the group.
     *
     * Authorization is checked at the controller or policy level to ensure the user has permission
     * to add players to the group (e.g., group admin or designated role).
     *
     * @return bool Always true; group-level authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the group player creation request data.
     *
     * Ensures the player name is unique within the group and the group has not exceeded its
     * player limit. Validates the player_name field.
     *
     * @return array<string, ValidationRule|array|string> The player field validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
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
