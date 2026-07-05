<?php

namespace App\Traits;

/**
 * Provides reusable normalization utilities for inconsistent external import payloads.
 */
trait ImportSourceDataHelpers
{
    /**
     * Resolves the first non-null payload value across an ordered list of candidate keys.
     *
     * If a key exists but its value is null, this method keeps searching so
     * alternate keys can still provide data. If no non-null key is found,
     * the provided default is returned.
     * This is useful for handling inconsistent field naming across different import sources.
     *
     * @param  array<string, mixed>  $payload  The raw data payload from the import source.
     * @param  array<int, string>  $keys  Candidate keys to check in order of preference.
     * @param  mixed  $default  Value to return when no candidate key holds a non-null value.
     * @return mixed The value of the first matching key, or the default.
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
     * Resolves a single-key payload value with the same null-handling semantics as valueForAny().
     *
     * Convenience wrapper for the common single-key case to avoid passing a one-element array.
     *
     * @param  array<string, mixed>  $payload  The raw data payload from the import source.
     * @param  string  $key  The key to look for in the payload.
     * @param  mixed  $default  Value to return when the key is absent or its value is null.
     * @return mixed The value at the key if present and non-null, or the default.
     */
    protected function valueFor(array $payload, string $key, mixed $default): mixed
    {
        return $this->valueForAny($payload, [$key], $default);
    }

    /**
     * Normalizes optional text fields into trimmed strings or null.
     *
     * Useful for import payloads where a field may be absent, explicitly null,
     * or a whitespace-only string — all of which should be treated as "no value".
     *
     * @param  mixed  $value  The raw value to normalize.
     * @return string|null The trimmed string if non-empty, or null otherwise.
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
     * Normalizes optional hex color values into canonical lowercase format.
     *
     * Accepts colors in "#RRGGBB" format (case-insensitive). The sentinel value
     * "#null" (any casing) is treated as an explicit absence of color, which some
     * import sources use to signal "no color" rather than omitting the field.
     *
     * @param  mixed  $value  The raw value to normalize.
     * @return string|null The lowercased hex color string if valid, or null otherwise.
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
     * Normalizes Twitter handle-like input into a usable profile URL.
     *
     * Accepts a full URL (returned as-is) or a bare handle with or without the
     * leading '@'. Constructs an x.com URL when only a handle is provided. Some
     * import sources store only the handle, so this prevents storing unusable
     * partial values in the database.
     *
     * @param  mixed  $value  The raw value to normalize.
     * @return string|null A fully-qualified profile URL, or null if the input is blank or unparseable.
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
     * Normalizes list-like URL input into a dense array of valid URLs.
     *
     * Import sources may provide logo/social URL lists as a single string, a sparse
     * array containing empty values, or other inconsistent shapes. This method
     * normalises all of those cases to a dense array of valid URLs, or null when
     * no valid URLs are found.
     *
     * @param  mixed  $value  The raw value to normalize.
     * @return array<int, mixed>|null A dense array of valid URL strings, or null if none are found.
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
