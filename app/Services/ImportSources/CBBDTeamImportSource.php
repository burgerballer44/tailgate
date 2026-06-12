<?php

namespace App\Services\ImportSources;

use App\Clients\CBBDApiClient;
use App\DTO\ImportedTeamData;
use App\DTO\ImportFetchStream;
use App\DTO\TeamImportData;
use App\Models\Sport;
use App\Models\Team;
use App\Models\TeamType;
use App\Services\Contracts\TeamImportSourceInterface;
use App\Traits\ImportSourceDataHelpers;

/**
 * Supplies basketball team data from the CBBD API in the normalized team import shape.
 * Maps source-specific payloads into application-ready team records and metadata.
 */
class CBBDTeamImportSource implements TeamImportSourceInterface
{
    use ImportSourceDataHelpers;

    public function __construct(private readonly CBBDApiClient $client) {}

    public function key(): string
    {
        return 'cbbd';
    }

    public function label(): string
    {
        return 'CBBD API';
    }

    public function type(): string
    {
        return 'api';
    }

    public function description(): string
    {
        return 'Imports basketball teams from CollegeBasketballData.';
    }

    /**
     * @return ImportFetchStream<ImportedTeamData>
     */
    public function fetch(TeamImportData $data): ImportFetchStream
    {
        $rawTeamStream = $this->client->fetchTeams([
            'year' => $data->options['year'] ?? null,
            'conference' => $data->options['conference'] ?? null,
        ]);

        return new ImportFetchStream(
            (function () use ($rawTeamStream): \Generator {
                $errors = [];

                foreach ($rawTeamStream as $index => $rawTeam) {
                    $teamRow = $index + 1;
                    $teamId = $this->valueForAny($rawTeam, ['id', 'teamId', 'team_id'], $teamRow);

                    $organization = $this->valueForAny($rawTeam, ['school', 'team', 'name'], Team::UNKNOWN_ORGANIZATION);
                    $conference = $this->valueForAny($rawTeam, ['conference'], Team::UNKNOWN_CONFERENCE);

                    if (
                        ! is_string($organization)
                        || blank(trim($organization))
                        || ! is_string($conference)
                        || blank(trim($conference))
                    ) {
                        $errors[] = "Skipped CBBD team {$teamId}: required fields were missing from the response.";

                        continue;
                    }

                    $socialMedia = [];
                    $twitter = $this->normalizeTwitterUrl($this->valueForAny($rawTeam, ['twitter', 'x'], null));

                    if ($twitter !== null) {
                        $socialMedia[] = ['label' => 'X', 'url' => $twitter];
                    }

                    yield new ImportedTeamData(
                        organization: trim($organization),
                        sport: Sport::BASKETBALL->value,
                        type: TeamType::COLLEGE->value,
                        conference: trim($conference),
                        designation: $this->nullableString($this->valueForAny($rawTeam, ['mascot', 'nickname'], null)),
                        abbreviation: $this->nullableString($this->valueForAny($rawTeam, ['abbreviation', 'abbrev'], null)),
                        color: $this->nullableHexColor($this->valueForAny($rawTeam, ['color', 'primaryColor', 'primary_color'], null)),
                        logos: $this->nullableUrlArray($this->valueForAny($rawTeam, ['logos', 'logo'], null)),
                        socialMedia: $socialMedia === [] ? null : $socialMedia,
                    );
                }

                return $errors;
            })(),
        );
    }
}
