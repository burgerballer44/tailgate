<?php

namespace App\Services\Contracts;

use App\DTO\QuickPredictionPayload;
use App\Models\User;

/**
 * Builds the data payload for the dashboard quick-predictions modal.
 *
 * Implementations are responsible for scoping the payload to the user's approved
 * memberships and filtering games to the active prediction window.
 */
interface QuickPredictionServiceInterface
{
    /**
     * Return the human-readable prediction window label for dashboard UI copy.
     */
    public static function predictionWindowLabel(): string;

    /**
     * Return the full quick-prediction payload for the given user's dashboard modal.
     *
     * @return QuickPredictionPayload
     */
    public function getQuickPredictionsPayloadForUser(User $user): QuickPredictionPayload;
}
