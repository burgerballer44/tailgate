<?php

namespace App\Services;

use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedScoreData;
use App\Models\Member;
use App\Models\Player;
use App\Models\Score;
use App\Services\Contracts\PlayerCommandInterface;

/**
 * Executes player lifecycle actions within member-owned rosters.
 * Covers player persistence and score submission workflows used by prediction features.
 */
class PlayerCommandService implements PlayerCommandInterface
{
    /**
     * Adds a player to a member roster using normalized player input.
     *
     * @param  Member  $member  The member to add the player to.
     * @param  ValidatedPlayerData  $data  Validated player data including player_name.
     * @return Player  The created player instance.
     */
    public function createForMember(Member $member, ValidatedPlayerData $data): Player
    {
        $playerData = [
            'player_name' => $data->player_name,
        ];

        return $member->players()->create($playerData);
    }

    /**
     * Applies name and member-association changes to an existing player.
     *
     * @param  Player  $player  The player to update.
     * @param  ValidatedPlayerData  $data  Validated data containing player information to update.
     * @return Player  The updated player instance.
     */
    public function update(Player $player, ValidatedPlayerData $data): Player
    {
        $updateData = [];

        if ($data->player_name !== null) {
            $updateData['player_name'] = $data->player_name;
        }

        if ($data->member_id !== null) {
            $updateData['member_id'] = $data->member_id;
        }

        $player->fill($updateData);
        $player->save();

        return $player;
    }

    /**
     * Removes a player record from persistence.
     *
     * @param  Player  $player  The player to delete.
     */
    public function delete(Player $player): void
    {
        $player->delete();
    }

    /**
     * Persists a new score prediction for a player-game pairing.
     *
     * @param  Player  $player  The player to submit the score for.
     * @param  ValidatedScoreData  $data  Validated score data.
     * @return Score  The created score instance.
     */
    public function submitScore(Player $player, ValidatedScoreData $data): Score
    {
        $scoreData = [
            'game_id' => $data->game_id,
            'home_team_prediction' => $data->home_team_prediction,
            'away_team_prediction' => $data->away_team_prediction,
        ];

        return $player->scores()->create($scoreData);
    }

    /**
     * Applies prediction changes to an existing score record.
     *
     * @param  Score  $score  The score to update.
     * @param  ValidatedScoreData  $data  Validated data containing score information to update.
     * @return Score  The updated score instance.
     */
    public function updateScore(Score $score, ValidatedScoreData $data): Score
    {
        $updateData = [
            'home_team_prediction' => $data->home_team_prediction,
            'away_team_prediction' => $data->away_team_prediction,
        ];

        $score->fill($updateData);
        $score->save();

        return $score;
    }

    /**
     * Removes a submitted score record from persistence.
     *
     * @param  Score  $score  The score to delete.
     */
    public function deleteScore(Score $score): void
    {
        $score->delete();
    }
}
