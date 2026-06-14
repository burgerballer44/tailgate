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
    public function createForMember(Member $member, ValidatedPlayerData $data): Player;

    public function update(Player $player, ValidatedPlayerData $data): Player;

    public function delete(Player $player): void;

    public function submitPrediction(Player $player, ValidatedPredictionData $data): Prediction;

    public function updatePrediction(Prediction $prediction, ValidatedPredictionData $data): Prediction;

    public function deletePrediction(Prediction $prediction): void;
}
