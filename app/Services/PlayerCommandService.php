<?php

namespace App\Services;

use App\DTO\ValidatedPlayerData;
use App\DTO\ValidatedPredictionData;
use App\Exceptions\PredictionPolicyViolationException;
use App\Models\Member;
use App\Models\Player;
use App\Models\Prediction;
use App\Services\Contracts\PlayerCommandInterface;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\PredictionPolicyEvaluatorService;

/**
 * Executes player lifecycle actions within member-owned rosters.
 * Covers player persistence and prediction submission workflows used by prediction features.
 */
class PlayerCommandService implements PlayerCommandInterface
{
    /**
     * Create a player coordinator with optional policy evaluation support.
     *
     * @param PredictionPolicyEvaluatorInterface|null $predictionPolicyEvaluator The evaluator used to validate prediction submissions; defaults to the concrete service when omitted.
     */
    public function __construct(
        private ?PredictionPolicyEvaluatorInterface $predictionPolicyEvaluator = new PredictionPolicyEvaluatorService()
    ) {}

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
        Player::destroy($player->getKey());
    }

    /**
     * Persists a new prediction for a player-game pairing.
     *
     * @param  Player  $player  The player to submit the prediction for.
     * @param  ValidatedPredictionData  $data  Validated prediction data.
     * @return Prediction  The created prediction instance.
     * @throws PredictionPolicyViolationException  If the prediction violates any business rules.
     */
    public function submitPrediction(Player $player, ValidatedPredictionData $data): Prediction
    {
        $result = $this->predictionPolicyEvaluator->evaluate($player, $data);

        if ($result->hasViolations()) {
            throw new PredictionPolicyViolationException($result);
        }

        $predictionData = [
            'game_id' => $data->game_id,
            'home_team_prediction' => $data->home_team_prediction,
            'away_team_prediction' => $data->away_team_prediction,
        ];

        return $player->predictions()->create($predictionData);
    }

    /**
     * Applies prediction changes to an existing prediction record.
     *
     * @param  Prediction  $prediction  The prediction to update.
     * @param  ValidatedPredictionData  $data  Validated data containing prediction information to update.
     * @return Prediction  The updated prediction instance.
     * @throws PredictionPolicyViolationException  If the updated prediction violates any business rules.
     */
    public function updatePrediction(Prediction $prediction, ValidatedPredictionData $data): Prediction
    {
        $result = $this->predictionPolicyEvaluator->evaluate($prediction->player, $data, $prediction);

        if ($result->hasViolations()) {
            throw new PredictionPolicyViolationException($result);
        }

        $updateData = [
            'home_team_prediction' => $data->home_team_prediction,
            'away_team_prediction' => $data->away_team_prediction,
        ];

        $prediction->fill($updateData);
        $prediction->save();

        return $prediction;
    }

    /**
     * Removes a submitted prediction record from persistence.
     *
     * @param  Prediction  $prediction  The prediction to delete.
     */
    public function deletePrediction(Prediction $prediction): void
    {
        Prediction::destroy($prediction->getKey());
    }
}
