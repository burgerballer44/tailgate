<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupSeasonFollowsData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;

class UpdateGroupSeasonFollowsRequest extends FormRequest
{
    use GroupValidationRulesTrait;

    /**
     * Authorize authenticated users to update the group's followed seasons.
     *
     * Group admin authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate explicit season-follow selections.
     *
     * Empty selections are allowed so admins can remove all season follows.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'season_ids' => ['sometimes', 'array'],
            'season_ids.*' => ['distinct', 'integer', 'in:'.implode(',', $this->activeSeasonIdsForFollow())],
        ];
    }

    /**
     * Convert validated input into a season-follow DTO.
     */
    public function toDTO(): ValidatedGroupSeasonFollowsData
    {
        return ValidatedGroupSeasonFollowsData::fromArray($this->validated());
    }
}
