<?php

namespace App\Http\Requests\Traits;

use App\Rules\UserMustBeAMember;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\Contracts\PredictionScoringPolicyCatalogInterface;
use App\Services\Contracts\SeasonQueryInterface;

trait GroupValidationRulesTrait
{
    /**
     * Define base validation rules for group data.
     *
     * Allows optional updates to group name and member/player limits while ensuring they conform
     * to reasonable constraints.
     *
     * @return array<string, ValidationRule|array|string> The base group field validation rules.
     */
    public function baseRules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'member_limit' => ['nullable', 'integer', 'max:50'],
            'player_limit' => ['nullable', 'integer', 'max:10'],
            'follow_limit' => ['nullable', 'integer', 'max:10'],
        ];
    }

    /**
     * Define validation rules for developer group updates including owner changes.
     *
     * Allows developers to change group ownership, which is restricted from the user-facing update endpoint.
     * The new owner must exist as a user in the system.
     *
     * @return array<string, ValidationRule|array|string> The admin group field validation rules.
     */
    public function developerUpdateRules(): array
    {
        return array_merge($this->baseRules(), [
            'owner_id' => ['required', new UserMustBeAMember],
        ]);
    }

    /**
     * Define validation rules for creating a group.
     *
     * Requires a group name and valid owner. The owner must exist as a user and be a member of the system.
     *
     * @return array<string, ValidationRule|array|string> The group creation validation rules.
     */
    public function storeRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'owner_id' => ['required', 'exists:users,id'],
            'member_limit' => ['nullable', 'integer', 'max:50'],
            'player_limit' => ['nullable', 'integer', 'max:10'],
            'follow_limit' => ['nullable', 'integer', 'max:10'],
        ];
    }

    /**
     * Returns the valid string keys for group-level prediction policies.
     *
     * Resolved from the PredictionPolicyEvaluator service so that adding a new policy
     * class automatically makes its key a valid input value without touching validation code.
     *
     * @return array<int, string>
     */
    public function groupPolicyKeys(): array
    {
        return array_values(array_map(
            static fn ($rule): string => $rule->key(),
            app(PredictionPolicyEvaluatorInterface::class)->groupRules(),
        ));
    }

    /**
     * Returns the valid keys for prediction scoring policy selection.
     *
     * @return array<int, string>
     */
    public function predictionScoringPolicyKeys(): array
    {
        return app(PredictionScoringPolicyCatalogInterface::class)->keys();
    }

    /**
     * Returns the IDs of active seasons available for explicit following.
     *
     * @return array<int, int>
     */
    public function activeSeasonIdsForFollow(): array
    {
        return app(SeasonQueryInterface::class)
            ->getAvailableSeasonsForFollow()
            ->pluck('id')
            ->map(fn ($seasonId): int => (int) $seasonId)
            ->all();
    }
}
