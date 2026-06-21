<?php

namespace App\Services\ImportSources;

use App\Clients\CFBDApiClient;
use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use App\Services\Contracts\TeamImportSourceInterface;
use App\Traits\ImportSourceDataHelpers;

/**
 * Supplies football team data from the CFBD API in the normalized team import shape.
 * Maps source-specific payloads into application-ready team records and metadata.
 */
class CFBDTeamImportSource implements TeamImportSourceInterface
{
    use ImportSourceDataHelpers;

    public function __construct(private readonly CFBDApiClient $client) {}

    /**
     * Get the stable import key used to select this CFBD source.
     *
     * @return string The source key consumed by import managers and forms.
     */
    public function key(): string
    {
        return 'cfbd';
    }

    /**
     * Get the human-readable label shown in source selection UIs.
     *
     * @return string The display label for this source.
     */
    public function label(): string
    {
        return 'CFBD API';
    }

    /**
     * Identify this source as API-backed.
     *
     * @return string The source transport type used for display and grouping.
     */
    public function type(): string
    {
        return 'api';
    }

    /**
     * Describe the data feed for operator-facing import screens.
     *
     * @return string A short description of the CFBD football team feed.
     */
    public function description(): string
    {
        return 'Imports football teams from CollegeFootballData.';
    }

    /**
     * Stream normalized CFBD team records for the requested import options.
     *
     * @param TeamImportData $data Import options including year and conference filters.
     * @return ImportFetchStream<ImportedTeamData> A stream of normalized team DTOs plus any fetch-time errors.
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
                    $teamId = $this->valueForAny($rawTeam, ['id'], $teamRow);

                    // validate required fields and skip teams with missing data
                    $organization = $this->valueForAny($rawTeam, ['school'], Team::UNKNOWN_ORGANIZATION);
                    $conference = $this->valueForAny($rawTeam, ['conference'], Team::UNKNOWN_CONFERENCE);

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
                    $twitter = $this->normalizeTwitterUrl($this->valueForAny($rawTeam, ['twitter'], null));

                    if ($twitter !== null) {
                        $socialMedia[] = ['label' => 'X', 'url' => $twitter];
                    }

                    yield new ImportedTeamData(
                        organization: trim($organization),
                        sport: Sport::FOOTBALL->value, // CFBD only provides football teams
                        type: TeamType::COLLEGE->value, // CFBD only provides college teams
                        conference: trim($conference),
                        designation: $this->nullableString($this->valueForAny($rawTeam, ['mascot'], null)),
                        abbreviation: $this->nullableString($this->valueForAny($rawTeam, ['abbreviation'], null)),
                        color: $this->nullableHexColor($this->valueForAny($rawTeam, ['color'], null)),
                        logos: $this->nullableUrlArray($this->valueForAny($rawTeam, ['logos'], null)),
                        socialMedia: empty($socialMedia) ? null : $socialMedia,
                    );
                }

                return $errors;
            })()
        );
    }
}
