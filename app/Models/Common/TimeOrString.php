<?php

namespace App\Models\Common;

use DateTimeImmutable;

// some time fields need to be able to hold things like 'TBD'
class TimeOrString
{
    // HH-ii
    public const TIME_FORMAT = 'H:i';

    private $time;

    private function __construct(string $time)
    {
        $this->time = $time;
    }

    public static function fromString(string $time): self
    {
        // check if it is a time
        $dateTime = DateTimeImmutable::createFromFormat(self::TIME_FORMAT, $time);

        // if it is then store it as such otherwise keep the string
        $time = $dateTime instanceof DateTimeImmutable ? $dateTime->format(self::TIME_FORMAT) : $time;

        return new self($time);
    }

    public function __toString(): string
    {
        return (string) $this->time;
    }
}
