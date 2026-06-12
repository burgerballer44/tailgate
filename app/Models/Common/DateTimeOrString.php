<?php

namespace App\Models\Common;

use DateTimeImmutable;

/**
 * Preserves uncertain scheduling values while normalizing valid UTC timestamps.
 *
 * Accepts either a strict datetime string or a placeholder such as 'TBD', allowing import flows
 * to carry unresolved kickoff/tip-off values without losing formatting consistency.
 */
class DateTimeOrString
{
    /**
     * The expected UTC datetime format with millisecond precision.
     * Example: 2026-08-29T04:00:00.000Z
     */
    public const DATE_FORMAT = 'Y-m-d\TH:i:s.v\Z';

    private $dateTime;

    /**
     * Stores a normalized or passthrough datetime token.
     *
     * @param  string  $dateTime  The raw date-time string or placeholder value.
     */
    private function __construct(string $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * Normalizes strict UTC datetime input while preserving unresolved placeholders.
     *
     * Attempts to parse the input string as a UTC datetime in the expected format.
     * If parsing succeeds, the datetime is normalized to the standard format.
     * If parsing fails, the original string is preserved as-is (e.g., 'TBD').
     *
     * @param  string  $dateTime  The date-time string or placeholder value to parse.
     * @return self  A new DateTimeOrString instance.
     */
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

    /**
     * Exposes the persisted datetime token for serialization and display.
     *
     * @return string  The normalized date-time string or original placeholder value.
     */
    public function __toString(): string
    {
        return (string) $this->dateTime;
    }
}
