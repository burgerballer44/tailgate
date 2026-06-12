<?php

namespace App\Traits;

/**
 * Exposes consistent enum serialization helpers for forms, filters, and UI option maps.
 */
trait EnumToArray
{
    /**
     * Lists enum case names in declaration order.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Lists enum backing values in declaration order.
     *
     * @return array<int, string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Builds a value-to-name map suitable for select inputs.
     *
     * @return array<string|int, string>
     */
    public static function array(): array
    {
        return array_combine(self::values(), self::names());
    }
}
