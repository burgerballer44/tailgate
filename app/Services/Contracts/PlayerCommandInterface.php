<?php

namespace App\Services\Contracts;

use App\Models\Player;
use App\Models\Member;
use App\Models\Score;
use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedScoreData;

interface PlayerCommandInterface
{
    public function createForMember(Member $member, ValidatedPlayerData $data): Player;
    public function update(Player $player, ValidatedPlayerData $data): Player;
    public function delete(Player $player): void;
    public function submitScore(Player $player, ValidatedScoreData $data): Score;
    public function updateScore(Score $score, ValidatedScoreData $data): Score;
    public function deleteScore(Score $score): void;
}