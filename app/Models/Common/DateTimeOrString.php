<?php

namespace App\Models\Common;

use DateTimeImmutable;

// some date-time fields need to be able to hold things like 'TBD'
class DateTimeOrString
{
    // example: 2026-08-29T04:00:00.000Z
    public const DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    private $dateTime;

    private function __construct(string $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    public static function fromString(string $dateTime): self
    {
        // check if it is a date-time using the expected UTC millisecond format
        $parsedDateTime = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $dateTime);

        // if it is then store it in normalized format otherwise keep the original string
        $normalizedDateTime = $parsedDateTime instanceof DateTimeImmutable
            ? $parsedDateTime->format(self::DATE_FORMAT)
            : $dateTime;

        return new self($normalizedDateTime);
    }

    public function __toString(): string
    {
        return (string) $this->dateTime;
    }
}