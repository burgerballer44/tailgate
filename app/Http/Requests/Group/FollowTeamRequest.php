<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedFollowData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\FollowValidationRulesTrait;

class FollowTeamRequest extends FormRequest
{
    use FollowValidationRulesTrait;

    /**
     * Authorize the authenticated user to follow a team.
     *
     * Any authenticated user may follow a team. Authorization is checked at the controller or
     * policy level to ensure the user has permission within their group context.
     *
     * @return bool Always true; group-level authorization is enforced elsewhere.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate the team follow request data.
     *
     * Ensures the team exists and at least one active season is selected.
     *
     * @return array<string, mixed> The team and selected-season validation rules.
     */
    public function rules(): array
    {
        return $this->storeRules();
    }

    /**
     * Transform validated team follow data into a data transfer object for the service layer.
     *
     * @return ValidatedFollowData The validated follow data transfer object.
     */
    public function toDTO(): ValidatedFollowData
    {
        return ValidatedFollowData::fromArray($this->validated());
    }
}
