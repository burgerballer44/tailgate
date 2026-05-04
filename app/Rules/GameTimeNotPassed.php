<?php

namespace App\Rules;

use App\Models\Game;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GameTimeNotPassed implements ValidationRule
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $game = Game::where('id', $value)->first();

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
