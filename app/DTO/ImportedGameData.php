<?php

namespace App\DTO;

readonly class ImportedGameData
{
    /**
     * Represents one normalized game record produced by an external import source.
     * Includes matchup identity, conference context, score values, and start-time semantics.
     *
     * @param string $homeTeam The name of the home team.
     * @param string $homeTeamConference The conference of the home team.
     * @param string $awayTeam The name of the away team.
     * @param string $awayTeamConference The conference of the away team.
     * @param int $homeTeamScore The score of the home team.
     * @param int $awayTeamScore The score of the away team.
     * @param string $startDateTime The start date and time of the game.
     * @param bool $startTimeTBD Whether the start time is to be determined (TBD).
     */
    public function __construct(
        public string $homeTeam,
        public string $homeTeamConference,
        public string $awayTeam,
        public string $awayTeamConference,
        public int $homeTeamScore,
        public int $awayTeamScore,
        public string $startDateTime,
        public bool $startTimeTBD,
    ) {}
}
