<?php

namespace App\Traits;

trait ImportSourceDataHelpers
{
    /**
     * Get the first non-null value from a list of possible keys.
     *
     * If a key exists but its value is null, this method keeps searching so
     * alternate keys can still provide data. If no non-null key is found,
     * the provided default is returned.
     * This is useful for handling inconsistent field naming across different import sources.
      *
      * @param  array<string, mixed>  $payload The raw data payload from the import source.
      * @param  array<int, string>  $keys A list of possible keys to check in order of preference.
      * @param  mixed  $default The default value to return if no keys are found with non-null values.
      * @return mixed The value corresponding to the first found key with a non-null value, or the default.
     */
    protected function valueForAny(array $payload, array $keys, mixed $default): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            if (isset($payload[$key])) {
                return $payload[$key];
            }
        }

        return $default;
    }

    /**
     * Get the value for a single key, returning the default if the key is not found or its value is null.
     *
     * This is a simpler version of valueForAny when only one key is expected.
     *
     * @param  array<string, mixed>  $payload The raw data payload from the import source.
     * @param  string  $key The key to look for in the payload.
     * @param  mixed  $default The default value to return if the key is not found or its value is null.
     * @return mixed The value corresponding to the key if it exists and is not null, or the default.
     */
    protected function valueFor(array $payload, string $key, mixed $default): mixed
    {
        return $this->valueForAny($payload, [$key], $default);
    }

    /**
     * Normalize a potential string value, returning null for non-string or empty values.
     *
     * This is useful for cleaning up data from import sources where fields may be inconsistently typed or formatted.
     *
     * @param  mixed  $value The value to normalize.
     * @return string|null The trimmed string if the input is a non-empty string, or null otherwise.
     */
    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Normalize a potential hex color value, returning null for invalid formats.
     *
     * This method accepts hex colors in the format "#RRGGBB" and is case-insensitive.
     * It also treats the string "#null" (in any casing) as a null value to allow import sources to explicitly indicate no color.
     *
     * @param  mixed  $value The value to normalize as a hex color.
     * @return string|null The normalized hex color string if valid, or null otherwise.
     */
    protected function nullableHexColor(mixed $value): ?string
    {
        $normalized = $this->nullableString($value);

        if ($normalized === null || strcasecmp($normalized, '#null') === 0) {
            return null;
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $normalized) === 1 ? strtolower($normalized) : null;
    }

    /**
     * Normalize a Twitter handle or URL into a full URL, returning null for invalid inputs.
     *
     * This method accepts either a full URL or a Twitter handle (with or without the '@' symbol).
     * If a handle is provided, it constructs the corresponding X.com URL. If the input is already a valid URL, it returns it as is.
     *
     * @param  mixed  $value The value to normalize as a Twitter URL.
     * @return string|null The normalized Twitter URL if valid, or null otherwise.
     */
    protected function normalizeTwitterUrl(mixed $value): ?string
    {
        $twitter = $this->nullableString($value);

        if ($twitter === null) {
            return null;
        }

        if (filter_var($twitter, FILTER_VALIDATE_URL) !== false) {
            return $twitter;
        }

        $handle = ltrim($twitter, '@');

        if ($handle === '') {
            return null;
        }

        return 'https://x.com/'.$handle;
    }

    /**
     * Normalize a potential list value into a dense array or null.
     * Empty strings, null entries, and empty nested arrays are removed.
     *
     * This is useful for cleaning up list-like fields from import sources where the data may be inconsistently formatted as a single string, an array with empty values, or other variations.
     *
     * @param  mixed  $value The value to normalize as an array.
     * @return array<int, mixed>|null A dense array of non-empty values if the input is valid, or null otherwise.
     */
    protected function nullableUrlArray(mixed $value): ?array
    {
        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_URL) !== false ? [$value] : null;
        }

        if (! is_array($value)) {
            return null;
        }

        $urls = array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && filter_var($item, FILTER_VALIDATE_URL) !== false));

        return $urls === [] ? null : $urls;
    }
}