<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Member;
use App\Models\Score;
use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedScoreData;
use Illuminate\Contracts\Database\Eloquent\Builder;

class PlayerService
{
    /**
     * Create a new player for a specific member.
     * This method handles player creation logic within a member context.
     *
     * @param Member $member The member to add the player to.
     * @param ValidatedPlayerData $data Validated player data including player_name.
     * @return Player The created player instance.
     */
    public function createForMember(Member $member, ValidatedPlayerData $data): Player
    {
        $playerData = [
            'player_name' => $data->player_name,
        ];

        return $member->players()->create($playerData);
    }

    /**
     * Update a player's information.
     * This method modifies player details.
     *
     * @param Player $player The player to update.
     * @param ValidatedPlayerData $data Validated data containing player information to update.
     * @return Player The updated player instance.
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
     * Delete a player from the system.
     * This method permanently removes the player.
     *
     * @param Player $player The player to delete.
     */
    public function delete(Player $player): void
    {
        $player->delete();
    }

    /**
     * Filter players based on the provided query parameters.
     * This method returns a query builder instance that can be further modified or executed.
     *
     * @param array $query An associative array of query parameters to filter players.
     * @return Builder A query builder instance for the filtered players.
     */
    public function query(array $query)
    {
        return Player::filter($query ?? []);
    }

    /**
     * Submit a score for a player.
     * This method handles score submission for a player.
     *
     * @param Player $player The player to submit the score for.
     * @param ValidatedScoreData $data Validated score data.
     * @return Score The created score instance.
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
     * Update a score.
     * This method modifies an existing score.
     *
     * @param Score $score The score to update.
     * @param ValidatedScoreData $data Validated data containing score information to update.
     * @return Score The updated score instance.
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
     * Delete a score.
     * This method permanently removes a score.
     *
     * @param Score $score The score to delete.
     */
    public function deleteScore(Score $score): void
    {
        $score->delete();
    }
}