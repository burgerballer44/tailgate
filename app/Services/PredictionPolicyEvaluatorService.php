<?php

namespace App\Services;

use App\DTO\PredictionPolicyContext;
use App\DTO\PredictionPolicyEvaluationResult;
use App\DTO\PredictionPolicyViolation;
use App\DTO\ValidatedPredictionData;
use App\Models\Game;
use App\Models\Group;
use App\Models\Player;
use App\Models\Prediction;
use App\PredictionPolicies\PredictionLockTimePolicy;
use App\PredictionPolicies\MinimumLeadTimeBeforeLockPolicy;
use App\PredictionPolicies\SeasonActivePolicy;
use App\PredictionPolicies\UniqueGroupPredictionPolicy;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Evaluates prediction submissions against application-wide and group-specific policy rules.
 */
class PredictionPolicyEvaluatorService implements PredictionPolicyEvaluatorInterface
{
    /**
     * Evaluate a prediction submission by building policy context and collecting rule violations.
     *
     * App-level rules always run. Group-level rules only run when the group has
     * enabled the matching policy key.
     *
     * @param Player $player The player submitting or updating a prediction.
     * @param ValidatedPredictionData $submission The normalized prediction payload.
     * @param Prediction|null $prediction The existing prediction when evaluating an update, or null for a new submission.
     * @return PredictionPolicyEvaluationResult The collected evaluation outcome, including any recorded violations.
     *
     * @throws \RuntimeException When the player does not belong to a group or the target game cannot be resolved.
     */
    public function evaluate(Player $player, ValidatedPredictionData $submission, ?Prediction $prediction = null): PredictionPolicyEvaluationResult
    {
        // load the group relationship once so rule resolution can inspect group settings
        $player->loadMissing('member.group');

        // resolve the target game for this submission or existing prediction
        $game = $this->resolveGame($submission, $prediction);
        // the player's group determines which optional group-level rules should run
        $group = $player->member?->group;

        if (! $group instanceof Group) {
            throw new \RuntimeException('Prediction policies require the player to belong to a group.');
        }

        // Package the full evaluation state into a single context object so every rule
        // receives the same view of the submission, the player, the group, and the game.
        $context = new PredictionPolicyContext(
            player: $player,
            group: $group,
            game: $game,
            submission: $submission,
            prediction: $prediction,
        );

        $violations = [];

        // app-level rules are always enforced for every submission
        foreach ($this->appRules() as $rule) {
            if ($rule->passes($context)) {
                continue;
            }

            $violations[] = new PredictionPolicyViolation(
                key: $rule->key(),
                label: $rule->label(),
                description: $rule->description(),
                scope: $rule->scope(),
            );
        }

        // group-level rules are only evaluated when the rule key is enabled on the group
        foreach ($this->enabledGroupRules($group) as $rule) {
            if ($rule->passes($context)) {
                continue;
            }

            $violations[] = new PredictionPolicyViolation(
                key: $rule->key(),
                label: $rule->label(),
                description: $rule->description(),
                scope: $rule->scope(),
            );
        }

        return new PredictionPolicyEvaluationResult($violations);
    }

    /**
     * Gets the list of app-level rules that are always enforced for every prediction submission.
        *
     * @return array<int, PredictionPolicyRuleInterface>
     */
    public function appRules(): array
    {
        return $this->makeRules([
            PredictionLockTimePolicy::class,
            SeasonActivePolicy::class,
        ]);
    }

    /**
     * Gets the list of available group-level rules that can be enabled on a per-group basis.
        *
     * @return array<int, PredictionPolicyRuleInterface>
     */
    public function groupRules(): array
    {
        return $this->makeRules([
            UniqueGroupPredictionPolicy::class,
            MinimumLeadTimeBeforeLockPolicy::class,
        ]);
    }

    /**
     * Gets the list of enabled group-level rules for the given group.
        *
        * @param Group $group The group whose enabled policy keys should be resolved.
     * @return array<int, PredictionPolicyRuleInterface>
     */
    public function enabledGroupRules(Group $group): array
    {
        return array_values(array_filter(
            $this->groupRules(),
            fn (PredictionPolicyRuleInterface $rule): bool => $group->isPredictionPolicyEnabled($rule->key())
        ));
    }

    /**
     * Resolves the game for the given submission or existing prediction.
     * 
     * @param  ValidatedPredictionData  $submission
     * @param  Prediction|null  $prediction
     * @return Game
     */
    private function resolveGame(ValidatedPredictionData $submission, ?Prediction $prediction): Game
    {
        if ($prediction !== null) {
            $prediction->loadMissing('game.season');

            return $prediction->game;
        }

        $game = Game::query()->with('season')->find($submission->game_id);

        if (! $game instanceof Game) {
            throw new \RuntimeException('Prediction policies require a valid game.');
        }

        return $game;
    }

    /**
     * Creates instances of the given rule classes.
     * 
     * @param  array<int, class-string<PredictionPolicyRuleInterface>>  $ruleClasses
     * @return array<int, PredictionPolicyRuleInterface>
     */
    private function makeRules(array $ruleClasses): array
    {
        return array_map(
            static fn (string $ruleClass): PredictionPolicyRuleInterface => app($ruleClass),
            $ruleClasses
        );
    }
}