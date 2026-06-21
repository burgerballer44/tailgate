<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedPredictionData;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;

/**
 * Manages player creation, updates, and prediction submission workflows within a member context.
 * Handles player information management and the complete game prediction lifecycle (submit, update, delete),
 * supporting member-based player rosters and game prediction scoring.
 */
interface PlayerCommandInterface
{
    /**
     * Create a player for the given member.
     *
     * @param Member $member The member who owns the player.
     * @param ValidatedPlayerData $data The normalized player payload.
     * @return Player The created player instance.
     */
    public function createForMember(Member $member, ValidatedPlayerData $data): Player;

    /**
     * Update an existing player.
     *
     * @param Player $player The player to update.
     * @param ValidatedPlayerData $data The normalized player payload.
     * @return Player The updated player instance.
     */
    public function update(Player $player, ValidatedPlayerData $data): Player;

    /**
     * Delete a player.
     *
     * @param Player $player The player to delete.
     * @return void
     */
    public function delete(Player $player): void;

    /**
     * Submit a new prediction for a player.
     *
     * @param Player $player The player submitting the prediction.
     * @param ValidatedPredictionData $data The normalized prediction payload.
     * @return Prediction The created prediction instance.
     */
    public function submitPrediction(Player $player, ValidatedPredictionData $data): Prediction;

    /**
     * Update an existing prediction.
     *
     * @param Prediction $prediction The prediction to update.
     * @param ValidatedPredictionData $data The normalized prediction payload.
     * @return Prediction The updated prediction instance.
     */
    public function updatePrediction(Prediction $prediction, ValidatedPredictionData $data): Prediction;

    /**
     * Delete a prediction.
     *
     * @param Prediction $prediction The prediction to delete.
     * @return void
     */
    public function deletePrediction(Prediction $prediction): void;
}
