<?php

namespace App\Services\Contracts;

use App\DTO\QuickPredictionPayload;
use App\Models\User;

/**
 * Defines operations that assemble quick-prediction payload data.
 *
 * Implementations scope data to approved memberships and include only games
 * that fall within the configured prediction window.
 */
interface QuickPredictionServiceInterface
{
    /**
     * Return a human-readable label for the active prediction window.
     */
    public static function predictionWindowLabel(): string;

    /**
     * Return the full quick-prediction payload for a user.
     */
    public function getQuickPredictionsPayloadForUser(User $user): QuickPredictionPayload;
}
