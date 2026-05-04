<?php

namespace App\Services\ImportSources;

use App\Clients\CFBDApiClient;
use App\DTO\ImportFetchStream;
use App\DTO\ImportedTeamData;
use App\DTO\TeamImportData;
use App\Models\Sport;
use App\Models\TeamType;
use App\Services\Contracts\TeamImportSourceInterface;

class CFBDTeamImportSource implements TeamImportSourceInterface
{
    public function __construct(private readonly CFBDApiClient $client) {}

    public function key(): string
    {
        return 'cfbd';
    }

    public function label(): string
    {
        return 'CFBD API';
    }

    public function type(): string
    {
        return 'api';
    }

    public function description(): string
    {
        return 'Imports football teams from CollegeFootballData.';
    }

    /**
     * @return ImportFetchStream<ImportedTeamData>
     */
    public function fetch(TeamImportData $data): ImportFetchStream
    {
        // get the team stream from the CFBD API using the provided query options
        $rawTeamStream = $this->client->fetchTeams([
            'year' => $data->options['year'] ?? null,
            'conference' => $data->options['conference'] ?? null,
        ]);

        return new ImportFetchStream(
            (function () use ($rawTeamStream): \Generator {
                $errors = [];

                foreach ($rawTeamStream as $index => $rawTeam) {
                    // keep track of the team number for error reporting
                    // use the team ID from the API if available, otherwise fall back to the row number
                    $teamRow = $index + 1;
                    $teamId = $this->valueFor($rawTeam, 'id', $teamRow);

                    // validate required fields and skip teams with missing data
                    $organization = $this->valueFor($rawTeam, 'school');
                    $conference = $this->valueFor($rawTeam, 'conference');

                    if (
                        ! is_string($organization)
                        || blank(trim($organization))
                        || ! is_string($conference)
                        || blank(trim($conference))
                    ) {
                        $errors[] = "Skipped CFBD team {$teamId}: required fields were missing from the response.";

                        continue;
                    }

                    // normalize the twitter handle into a full URL if it's present
                    $socialMedia = [];
                    $twitter = $this->normalizeTwitterUrl($this->valueFor($rawTeam, 'twitter'));

                    if ($twitter !== null) {
                        $socialMedia[] = ['label' => 'X', 'url' => $twitter];
                    }

                    yield new ImportedTeamData(
                        organization: trim($organization),
                        sport: Sport::FOOTBALL->value, // CFBD only provides football teams
                        type: TeamType::COLLEGE->value, // CFBD only provides college teams
                        conference: trim($conference),
                        designation: $this->nullableString($this->valueFor($rawTeam, 'mascot')), // CFBD uses 'mascot' to represent our team designation
                        abbreviation: $this->nullableString($this->valueFor($rawTeam, 'abbreviation')),
                        color: $this->nullableHexColor($this->valueFor($rawTeam, 'color')),
                        alternateColor: $this->nullableHexColor($this->valueFor($rawTeam, 'alternateColor')),
                        logos: $this->nullableUrlArray($this->valueFor($rawTeam, 'logos')),
                        socialMedia: empty($socialMedia) ? null : $socialMedia,
                    );
                }

                return $errors;
            })()
        );
    }

    /**
     * Helper method to safely retrieve a value from the raw team data array, with an optional default if the key is missing.
     *
     * @param array $rawTeam The raw team data array from the CFBD API.
     * @param string $key The key to retrieve from the array.
     * @param mixed $default An optional default value to return if the key is not present in the array.
     * @return mixed The value from the array corresponding to the key, or the default value if the key is not present.
     */
    private function valueFor(array $rawTeam, string $key, mixed $default = null): mixed
    {
        return $rawTeam[$key] ?? $default;
    }

    /**
     * Normalizes a value into a nullable string. If the value is not a string, or if it's an empty or whitespace-only string, this method returns null.
     *
     * @param mixed $value The value to normalize as a nullable string.
     * @return string|null A trimmed string if the input is a non-empty string, or null otherwise.
     */
    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Returns the provided value if it's an array, or null if it's not an array.
     * This is used to ensure that certain fields are treated as arrays if they are present,
     * or null if they are missing or of the wrong type.
     *
     * @param mixed $value The value to normalize as a nullable array.
     * @return array|null
     */
    private function nullableArray(mixed $value): ?array
    {
        return is_array($value) ? array_values($value) : null;
    }

    /**
     * Validates that the provided value is a hex color code in the format "#RRGGBB".
     * If the value is not a string, or if it doesn't match the expected format, this method returns null.
     * If the value is the string "#null" (case-insensitive), this method also returns null to allow for explicit null values.
     * Otherwise, it returns the normalized hex color code in lowercase.
     *
     * @param mixed $value The value to validate and normalize as a hex color code.
     * @return string|null A valid hex color code in the format "#rrggbb", or null if the input is not valid.
     */
    private function nullableHexColor(mixed $value): ?string
    {
        $normalized = $this->nullableString($value);

        if ($normalized === null || strcasecmp($normalized, '#null') === 0) {
            return null;
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $normalized) === 1 ? strtolower($normalized) : null;
    }

    /**
     * Normalizes a Twitter handle or URL into a full URL.
     * If the input is already a valid URL, it is returned as-is.
     * If the input is a non-empty string that is not a valid URL, it is treated as a Twitter handle and converted into a URL.
     * If the input is empty or cannot be normalized into a valid URL, this method returns null.
     *
     * @param mixed $value The value to normalize as a Twitter URL.
     * @return string|null A valid Twitter URL, or null if the input cannot be normalized.
     */
    private function normalizeTwitterUrl(mixed $value): ?string
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
     * Validates that the provided value is an array of valid URLs.
     * If the value is not an array, or if it doesn't contain any valid URLs, this method returns null.
     * Otherwise, it returns an array of valid URLs.
     *
     * @param mixed $value The value to validate and normalize as an array of URLs.
     * @return array|null An array of valid URLs if the input is a valid array
     */
    private function nullableUrlArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $urls = array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && filter_var($item, FILTER_VALIDATE_URL) !== false));

        return $urls === [] ? null : $urls;
    }
}
