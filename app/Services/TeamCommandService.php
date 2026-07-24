<?php

namespace App\Services;

use App\DTO\ValidatedTeamData;
use App\Models\Enums\Sport;
use App\Models\Team;
use App\Services\Contracts\TeamCommandInterface;

/**
 * Executes team lifecycle actions and maintains team-to-sport associations.
 * Centralizes persistence behavior for team identity, classification, metadata,
 * and sport-specific conference assignments.
 */
class TeamCommandService implements TeamCommandInterface
{
    /**
     * Persists a new team with normalized identity, metadata, and sport associations.
     *
     * @param  ValidatedTeamData  $data  Validated team data including identity, metadata, sports,
     *                                   and per-sport conference mappings.
     * @return Team The created team instance with any sport relations attached.
     */
    public function create(ValidatedTeamData $data): Team
    {
        // Build the core team payload before persisting relationships.
        $teamData = [
            'organization' => $data->organization,
            'designation' => $data->designation,
            'abbreviation' => $data->abbreviation,
            'color' => $data->color,
            'logos' => $data->logos,
            'social_media' => $data->socialMedia,
            'type' => $data->type->value,
        ];

        // Persist the base team first so relationship records have a foreign key.
        $team = Team::create($teamData);

        // batch-insert all sports in a single query rather than one insert per sport
        if (isset($data->sports) && ! empty($data->sports)) {
            $team->sports()->createMany(
                array_map(fn ($sport) => [
                    'sport' => $sport->value,
                    'conference' => $data->sportConferences[$sport->value] ?? $data->conference,
                ], $data->sports)
            );
        }

        return $team;
    }

    /**
     * Applies identity, metadata, and sport-association changes to an existing team.
     *
     * @param  Team  $team  The team to update.
     * @param  ValidatedTeamData  $data  Validated data to apply to the team, including any
     *                                   sport-specific conference changes.
     * @return Team The updated team instance.
     */
    public function update(Team $team, ValidatedTeamData $data): Team
    {
        // Team data properties are never expected to be null or set to null.
        $updateData = [
            'organization' => $data->organization,
            'designation' => $data->designation,
            'abbreviation' => $data->abbreviation,
            'color' => $data->color,
            'logos' => $data->logos,
            'social_media' => $data->socialMedia,
            'type' => $data->type->value,
        ];

        // Persist top-level fields before syncing sport associations.
        $team->fill($updateData);
        $team->save();

        // sync sports by diffing incoming against existing to avoid unnecessary deletes and re-inserts
        if (isset($data->sports) && ! empty($data->sports)) {
            $incomingValues = array_map(fn ($sport) => $sport->value, $data->sports);
            $incomingConferenceBySport = [];

            foreach ($incomingValues as $sportValue) {
                $incomingConferenceBySport[$sportValue] = $data->sportConferences[$sportValue] ?? $data->conference;
            }

            // Load existing relations once to compute add/remove/update deltas.
            $existingSports = $team->sports()->get();
            $existingValues = $existingSports
                ->pluck('sport')
                ->map(fn ($sport) => $sport instanceof Sport ? $sport->value : (string) $sport)
                ->all();

            $toAdd = array_values(array_diff($incomingValues, $existingValues));
            $toRemove = array_values(array_diff($existingValues, $incomingValues));

            // Remove relations that are no longer part of the requested state.
            if ($toRemove !== []) {
                $team->sports()->whereIn('sport', $toRemove)->delete();
            }

            // Insert newly requested sport rows in a single query.
            if ($toAdd !== []) {
                $team->sports()->createMany(
                    array_map(fn ($value) => [
                        'sport' => $value,
                        'conference' => $incomingConferenceBySport[$value] ?? $data->conference,
                    ], $toAdd)
                );
            }

            // Update conference metadata for retained sport rows.
            foreach ($existingSports as $existingSport) {
                $sportValue = $existingSport->sport instanceof Sport
                    ? $existingSport->sport->value
                    : (string) $existingSport->sport;

                if (! in_array($sportValue, $incomingValues, true)) {
                    continue;
                }

                $incomingConference = $incomingConferenceBySport[$sportValue] ?? $data->conference;

                if ($existingSport->conference !== $incomingConference) {
                    $existingSport->conference = $incomingConference;
                    $existingSport->save();
                }
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
        // Delete by key to avoid mutating in-memory model state.
        Team::destroy($team->getKey());
    }
}
