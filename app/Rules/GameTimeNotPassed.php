<?php

namespace App\Rules;

use App\Models\Common\DateOrString;
use App\Models\Common\TimeOrString;
use App\Models\Game;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GameTimeNotPassed implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $game = Game::where('id', $value)->first();

        // get the date and time of the game
        $gameDateTime = \DateTimeImmutable::createFromFormat(
            DateOrString::DATE_FORMAT.' '.TimeOrString::TIME_FORMAT,
            $game->start_date.' '.$game->start_time
        );
        if ($gameDateTime instanceof \DateTimeImmutable) {
            $today = (new \DateTime('now'))->format(DateOrString::DATE_FORMAT.' '.TimeOrString::TIME_FORMAT);
            $gameStart = $gameDateTime->format(DateOrString::DATE_FORMAT.' '.TimeOrString::TIME_FORMAT);
            if ($gameStart < $today) {
                $fail('The start of the game has passed.');

                return;
            }
        }

        // if creating the date time object fails then the game time is probably 'TBA' or something like that so just use the game date
        $gameDateTime = $gameDateTime = \DateTimeImmutable::createFromFormat(DateOrString::DATE_FORMAT, $game->start_date);
        if ($gameDateTime instanceof \DateTimeImmutable) {
            $today = (new \DateTime('now'))->format(DateOrString::DATE_FORMAT);
            $gameStart = $gameDateTime->format(DateOrString::DATE_FORMAT);

            if ($gameStart < $today) {
                $fail('The start of the game has passed.');

                return;
            }
        }
    }
}
