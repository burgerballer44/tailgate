<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Blocks score updates once the related game is considered started.
 * Uses date-only comparison for TBD start times and full timestamp comparison otherwise.
 */
class GameTimeNotPassedForUpdate implements ValidationRule
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * Validates that score updates are still allowed for the related game start window.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $score = request()->route('score');
        $game = $score->game;

        $gameDateTime = date_create_immutable((string) $game->start_date_time);

        if (! $gameDateTime instanceof \DateTimeImmutable) {
            return;
        }

        if ($game->start_time_tbd) {
            $today = (new \DateTimeImmutable('now'))->format(self::DATE_FORMAT);
            $gameStart = $gameDateTime->format(self::DATE_FORMAT);

            if ($gameStart < $today) {
                $fail('The start of the game has passed.');
            }

            return;
        }

        if ($gameDateTime < new \DateTimeImmutable('now')) {
            $fail('The start of the game has passed.');
        }
    }
}
