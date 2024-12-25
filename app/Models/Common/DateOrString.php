<?php

namespace App\Models\Common;

use DateTimeImmutable;

// some date fields need to be able to hold things like 'TBD'
class DateOrString
{
    // YYYY-MM-DD
    public const DATE_FORMAT = 'Y-m-d';

    private $date;

    private function __construct(string $date)
    {
        $this->date = $date;
    }

    public static function fromString(string $date): self
    {
        // check if it is a date
        $dateTime = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $date);

        // if it is then store it as such otherwise keep the string
        $date = $dateTime instanceof DateTimeImmutable ? $dateTime->format(self::DATE_FORMAT) : $date;

        return new self($date);
    }

    public function __toString(): string
    {
        return (string) $this->date;
    }
}