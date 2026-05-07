<?php

namespace App\Models;

use App\Traits\EnumToArray;

enum HtmlEntity: string
{
    use EnumToArray;

    case CHECK_MARK = '&#x2705;';
    case RED_X = '&#x274C;';
    case WARNING = '&#x26A0;';
    case INFO = '&#x2139;';
    case STAR = '&#x2B50;';
    case FIRE = '&#x1F525;';
    case LOCK = '&#x1F512;';
    case UNLOCK = '&#x1F513;';
    case CLOCK = '&#x23F0;';
    case TROPHY = '&#x1F3C6;';
    case BASKETBALL = '&#127936;';
    case FOOTBALL = '&#127944;';
    case QUESTION_MARK = '&#10067;';

    public static function forBoolean(bool $value): self
    {
        return $value ? self::CHECK_MARK : self::RED_X;
    }

    public function entity(): string
    {
        return $this->value;
    }

    public function character(): string
    {
        return html_entity_decode($this->value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}