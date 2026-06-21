<?php

namespace App\DTO;

use App\Models\Game;
use App\Models\Group;
use App\Models\Player;
use App\Models\Prediction;

/**
 * Immutable context passed to every prediction policy during evaluation.
 *
 * It contains the actor, ownership scope, target game, submitted scores,
 * and optionally the existing prediction when validating an update flow.
 */
readonly class PredictionPolicyContext
{
    /**
     * @param Player $player The player attempting to submit or update a prediction.
     * @param Group $group The player's group used for group-level policy checks.
     * @param Game $game The game being predicted.
     * @param ValidatedPredictionData $submission The normalized incoming prediction payload.
     * @param Prediction|null $prediction Existing prediction when evaluating updates; null for new submissions.
     */
    public function __construct(
        public Player $player,
        public Group $group,
        public Game $game,
        public ValidatedPredictionData $submission,
        public ?Prediction $prediction = null,
    ) {}
}