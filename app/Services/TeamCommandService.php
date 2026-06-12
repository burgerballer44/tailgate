<?php

namespace App\Services;

use App\DTO\ValidatedTeamData;
use App\Models\Sport;
use App\Models\Team;
use App\Services\Contracts\TeamCommandInterface;

class TeamCommandService implements TeamCommandInterface
{
    /**
     * Create a new team with the provided data.
     * This method handles team creation logic, including setting organization, designation, conference, type, and sport.
     *
     * @param  ValidatedTeamData  $data  Validated team data including organization, designation, type, sport.
     * @return Team The created team instance.
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
     * Update an existing team's information in the system.
     * This method is used to modify team details such as organization, designation, type, or sport.
     *
     * @param  Team  $team  The team to update.
     * @param  ValidatedTeamData  $data  Validated data to update the team with.
     * @return Team The updated team instance.
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
     * Delete a team from the system.
     * This method permanently removes the team.
     *
     * @param  Team  $team  The team to delete.
     */
    public function delete(Team $team): void
    {
        $team->delete();
    }
}
