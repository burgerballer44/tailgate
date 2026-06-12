<?php

namespace App\Services\Contracts;

use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedScoreData;
use App\Models\Member;
use App\Models\Player;
use App\Models\Score;

/**
 * Manages player creation, updates, and score submission workflows within a member context.
 * Handles player information management and the complete game score lifecycle (submit, update, delete),
 * supporting member-based player rosters and game prediction scoring.
 */
interface PlayerCommandInterface
{
    public function createForMember(Member $member, ValidatedPlayerData $data): Player;

    public function update(Player $player, ValidatedPlayerData $data): Player;

    public function delete(Player $player): void;

    public function submitScore(Player $player, ValidatedScoreData $data): Score;

    public function updateScore(Score $score, ValidatedScoreData $data): Score;

    public function deleteScore(Score $score): void;
}
