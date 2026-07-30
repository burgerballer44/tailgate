<?php

namespace App\Http\Requests\Group;

use App\DTO\ValidatedGroupPredictionScoringPolicyData;
use App\Http\Requests\FormRequest;
use App\Http\Requests\Traits\GroupValidationRulesTrait;
use Illuminate\Validation\Rule;

class UpdateGroupPredictionScoringPolicyRequest extends FormRequest
{
    use GroupValidationRulesTrait;

    /**
     * Authorize authenticated users to update group scoring policy.
     *
     * Group admin authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate required season-scoped prediction scoring policy selection.
     *
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        $group = $this->route('group');

        return [
            'season_id' => [
                'required',
                'integer',
                Rule::exists('group_season_follows', 'season_id')->where(function ($query) use ($group) {
                    $query->where('group_id', $group->id ?? null);
                }),
            ],
            'prediction_scoring_policy' => ['required', 'string', 'in:'.implode(',', $this->predictionScoringPolicyKeys())],
        ];
    }

    /**
     * Convert validated request input to a scoring policy DTO.
     */
    public function toDTO(): ValidatedGroupPredictionScoringPolicyData
    {
        return ValidatedGroupPredictionScoringPolicyData::fromArray([
            'season_id' => $this->input('season_id'),
            'prediction_scoring_policy' => $this->input('prediction_scoring_policy'),
        ]);
    }
}
