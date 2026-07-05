<?php

namespace App\Services\Contracts;

use App\DTO\PredictionPolicyEvaluationResult;
use App\DTO\ValidatedPredictionData;
use App\Models\Group;
use App\Models\Player;
use App\Models\Prediction;

/**
 * Defines prediction policy evaluation operations.
 *
 * Implementations evaluate submissions, expose registered rules, and resolve
 * which group-level rules are currently enabled.
 */
interface PredictionPolicyEvaluatorInterface
{
    /**
     * Evaluates a submission for the given player and returns the full policy result.
     *
     * @param  Player  $player  The player making the submission.
     * @param  ValidatedPredictionData  $submission  The normalized prediction payload.
     * @param  Prediction|null  $prediction  The existing prediction when evaluating an update.
     * @return PredictionPolicyEvaluationResult The complete validation result with any violations.
     */
    public function evaluate(Player $player, ValidatedPredictionData $submission, ?Prediction $prediction = null): PredictionPolicyEvaluationResult;

    /**
     * Returns all app-level policy rules that are always enforced.
     *
     * @return array<int, PredictionPolicyRuleInterface> The always-on policy rules.
     */
    public function appRules(): array;

    /**
     * Returns the available group-level policy rules that can be enabled per group.
     *
     * @return array<int, PredictionPolicyRuleInterface> The configurable group-level policy rules.
     */
    public function groupRules(): array;

    /**
     * Returns the subset of group-level policies currently enabled for the given group.
     *
     * @param  Group  $group  The group whose enabled policies should be resolved.
     * @return array<int, PredictionPolicyRuleInterface> The enabled group-level policy rules.
     */
    public function enabledGroupRules(Group $group): array;
}
