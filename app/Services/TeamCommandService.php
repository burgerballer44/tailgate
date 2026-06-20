<?php

namespace App\Services;

use App\DTO\ValidatedTeamData;
use App\Models\Sport;
use App\Models\Team;
use App\Services\Contracts\TeamCommandInterface;

/**
 * Executes team lifecycle actions and maintains team-to-sport associations.
 * Centralizes persistence behavior for team identity, classification, and metadata.
 */
class TeamCommandService implements TeamCommandInterface
{
    /**
     * Persists a new team with normalized identity, metadata, and sport associations.
     *
     * @param  ValidatedTeamData  $data  Validated team data including organization, designation, type, sport.
     * @return Team  The created team instance.
     */
    public function create(ValidatedTeamData $data): Team
    {
        $teamData = [
            'organization' => $data->organization,
            'designation' => $data->designation,
            'conference' => $data->conference,
            'abbreviation' => $data->abbreviation,
            'color' => $data->color,
            'logos' => $data->logos,
            'social_media' => $data->socialMedia,
            'type' => $data->type->value,
        ];

        $team = Team::create($teamData);

        // batch-insert all sports in a single query rather than one insert per sport
        if (isset($data->sports) && ! empty($data->sports)) {
            $team->sports()->createMany(
                array_map(fn ($sport) => ['sport' => $sport->value], $data->sports)
            );
        }

        return $team;
    }

    /**
     * Applies identity, metadata, and sport-association changes to an existing team.
     *
     * @param  Team  $team  The team to update.
     * @param  ValidatedTeamData  $data  Validated data to update the team with.
     * @return Team  The updated team instance.
     */
    public function update(Team $team, ValidatedTeamData $data): Team
    {
        // Team data properties are never expected to be null or set to null.
        $updateData = [
            'organization' => $data->organization,
            'designation' => $data->designation,
            'conference' => $data->conference,
            'abbreviation' => $data->abbreviation,
            'color' => $data->color,
            'logos' => $data->logos,
            'social_media' => $data->socialMedia,
            'type' => $data->type->value,
        ];

        $team->fill($updateData);
        $team->save();

        // sync sports by diffing incoming against existing to avoid unnecessary deletes and re-inserts
        if (isset($data->sports) && ! empty($data->sports)) {
            $incomingValues = array_map(fn ($sport) => $sport->value, $data->sports);
            $existingValues = $team->sports()->pluck('sport')->map(fn ($s) => $s instanceof Sport ? $s->value : (string) $s)->all();

            $toAdd = array_values(array_diff($incomingValues, $existingValues));
            $toRemove = array_values(array_diff($existingValues, $incomingValues));

            if ($toRemove !== []) {
                $team->sports()->whereIn('sport', $toRemove)->delete();
            }

            if ($toAdd !== []) {
                $team->sports()->createMany(
                    array_map(fn ($value) => ['sport' => $value], $toAdd)
                );
            }
        }

        return $team;
    }

    /**
     * Removes a team record from persistence.
     *
     * @param  Team  $team  The team to delete.
     */
    public function delete(Team $team): void
    {
        Team::destroy($team->getKey());
    }
}
