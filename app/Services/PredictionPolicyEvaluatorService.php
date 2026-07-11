<?php

namespace App\Services;

use App\DTO\PredictionPolicyContext;
use App\DTO\PredictionPolicyEvaluationResult;
use App\DTO\PredictionPolicyViolation;
use App\DTO\ValidatedPredictionData;
use App\Models\Game;
use App\Models\Group;
use App\Models\GroupSeasonFollow;
use App\Models\Player;
use App\Models\Prediction;
use App\PredictionPolicies\MinimumLeadTimeBeforeLockPolicy;
use App\PredictionPolicies\PredictionLockTimePolicy;
use App\PredictionPolicies\SeasonActivePolicy;
use App\PredictionPolicies\UniqueGroupPredictionPolicy;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\Contracts\PredictionPolicyRuleInterface;

/**
 * Evaluates prediction submissions against registered policy rules.
 *
 * App-level rules are always enforced. Group-level rules are enforced only
 * when explicitly enabled for the game's followed season.
 */
class PredictionPolicyEvaluatorService implements PredictionPolicyEvaluatorInterface
{
    /**
     * Evaluate a prediction submission and collect policy violations.
     *
     * For updates, pass the existing prediction so rules can exclude it from
     * duplicate checks and similar comparisons.
     *
     * @param  Player  $player  The player submitting or updating a prediction.
     * @param  ValidatedPredictionData  $submission  The normalized prediction payload.
     * @param  Prediction|null  $prediction  The existing prediction when evaluating an update, or null for a new submission.
     * @return PredictionPolicyEvaluationResult The collected evaluation outcome, including any recorded violations.
     *
     * @throws \RuntimeException When the player does not belong to a group or the target game cannot be resolved.
     */
    public function evaluate(Player $player, ValidatedPredictionData $submission, ?Prediction $prediction = null): PredictionPolicyEvaluationResult
    {
        // Load the group once so every rule sees the same group settings.
        $player->loadMissing('member.group');

        // Resolve the game from submission data or the existing prediction.
        $game = $this->resolveGame($submission, $prediction);
        // The player's group determines which optional rules are enabled.
        $group = $player->member?->group;

        if (! $group instanceof Group) {
            throw new \RuntimeException('Prediction policies require the player to belong to a group.');
        }

        // Build one shared context object for all rule evaluations.
        $context = new PredictionPolicyContext(
            player: $player,
            group: $group,
            game: $game,
            submission: $submission,
            prediction: $prediction,
        );

        $violations = [];

        // App-level rules always run.
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

        $seasonFollow = $group->seasonFollows()
            ->where('season_id', $game->season_id)
            ->first();

        // Group-level rules run only when enabled on the followed season.
        foreach ($this->enabledGroupRules($seasonFollow) as $rule) {
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
     * Return app-level rules that are always enforced.
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
     * Return group-level rules that may be enabled per group.
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
     * Return only group-level rules enabled for the given followed season.
     *
     * @param  GroupSeasonFollow|null  $seasonFollow  The followed season whose enabled policy keys should be resolved.
     * @return array<int, PredictionPolicyRuleInterface>
     */
    public function enabledGroupRules(?GroupSeasonFollow $seasonFollow): array
    {
        $enabledKeys = $seasonFollow?->enabled_prediction_policies ?? [];

        return array_values(array_filter(
            $this->groupRules(),
            fn (PredictionPolicyRuleInterface $rule): bool => in_array($rule->key(), $enabledKeys, true)
        ));
    }

    /**
     * Resolve the game targeted by this evaluation.
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
     * Resolve rule class names from the container.
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
