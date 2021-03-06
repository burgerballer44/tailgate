<?php

namespace Tailgate\Domain\Model\Team;

use Buttercup\Protects\IdentifiesAggregate;
use Tailgate\Domain\Model\AbstractEntity;

class Team extends AbstractEntity
{
    private $teamId;
    private $designation;
    private $mascot;
    private $sport;

    protected function __construct($teamId, $designation, $mascot, $sport)
    {
        $this->teamId = $teamId;
        $this->designation = $designation;
        $this->mascot = $mascot;
        $this->sport = $sport;
    }

    // create a team
    public static function create(TeamId $teamId, $designation, $mascot, $sport)
    {
        $newTeam = new Team($teamId, $designation, $mascot, $sport);

        $newTeam->recordThat(new TeamAdded($teamId, $designation, $mascot, $sport));

        return $newTeam;
    }

    // create an empty team
    protected static function createEmptyEntity(IdentifiesAggregate $teamId)
    {
        return new Team($teamId, '', '', '');
    }

    public function getId()
    {
        return (string) $this->teamId;
    }

    public function getDesignation()
    {
        return $this->designation;
    }

    public function getMascot()
    {
        return $this->mascot;
    }

    public function getSport()
    {
        return $this->sport;
    }

    // updates the team's designation adn mascot
    public function update($designation, $mascot)
    {
        $this->applyAndRecordThat(new TeamUpdated($this->teamId, $designation, $mascot));
    }

    // delete team
    public function delete()
    {
        $this->applyAndRecordThat(new TeamDeleted($this->teamId));
    }

    protected function applyTeamAdded(TeamAdded $event)
    {
        $this->designation = $event->getDesignation();
        $this->mascot = $event->getMascot();
        $this->sport = $event->getSport();
    }

    protected function applyTeamUpdated(TeamUpdated $event)
    {
        $this->designation = $event->getDesignation();
        $this->mascot = $event->getMascot();
    }

    protected function applyTeamDeleted()
    {
    }
}
