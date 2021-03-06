<?php

namespace Tailgate\Tests\Infrastructure\Persistence\Projection\PDO;

use PHPUnit\Framework\TestCase;
use Tailgate\Domain\Model\Group\GroupId;
use Tailgate\Domain\Model\Season\Season;
use Tailgate\Domain\Model\Season\SeasonId;
use Tailgate\Domain\Model\Team\TeamAdded;
use Tailgate\Domain\Model\Team\TeamDeleted;
use Tailgate\Domain\Model\Team\TeamId;
use Tailgate\Domain\Model\Team\TeamUpdated;
use Tailgate\Infrastructure\Persistence\Projection\PDO\TeamProjection;

class PDOTeamProjectionTest extends TestCase
{
    private $pdoMock;
    private $pdoStatementMock;
    private $projection;

    public function setUp(): void
    {
        $this->pdoMock = $this->createMock(\PDO::class);
        $this->pdoStatementMock = $this->createMock(\PDOStatement::class);
        $this->projection = new TeamProjection($this->pdoMock);
    }

    public function testItCanProjectTeamAdded()
    {
        $event = new TeamAdded(TeamId::fromString('teamId'), 'designation', 'mascot', Season::SPORT_FOOTBALL);

        // the pdo mock should call prepare and return a pdostatement mock
        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('INSERT INTO `team` (team_id, designation, mascot, sport, created_at)
            VALUES (:team_id, :designation, :mascot, :sport, :created_at)')
            ->willReturn($this->pdoStatementMock);

        // execute method called once
        $this->pdoStatementMock
            ->expects($this->once())
            ->method('execute')
            ->with([
                ':team_id' => $event->getAggregateId(),
                ':designation' => $event->getDesignation(),
                ':mascot' => $event->getMascot(),
                ':sport' => $event->getSport(),
                ':created_at' => $event->getOccurredOn()->format('Y-m-d H:i:s')
            ]);

        $this->projection->projectTeamAdded($event);
    }

    public function testItCanProjectTeamUpdated()
    {
        $event = new TeamUpdated(TeamId::fromString('teamId'), 'designation', 'mascot');

        // the pdo mock should call prepare and return a pdostatement mock
        $this->pdoMock
            ->expects($this->once())
            ->method('prepare')
            ->with('UPDATE `team`
            SET designation = :designation, mascot = :mascot
            WHERE team_id = :team_id')
            ->willReturn($this->pdoStatementMock);

        // execute method called once
        $this->pdoStatementMock
            ->expects($this->once())
            ->method('execute')
            ->with([
                ':team_id' => $event->getAggregateId(),
                ':designation' => $event->getDesignation(),
                ':mascot' => $event->getMascot()
            ]);

        $this->projection->projectTeamUpdated($event);
    }

    public function testItCanProjectTeamDeleted()
    {
        $event = new TeamDeleted(TeamId::fromString('teamId'));

        // the pdo mock should call prepare and return a pdostatement mock
        $this->pdoMock
            ->expects($this->at(0))
            ->method('prepare')
            ->with('DELETE FROM `score` WHERE game_id IN
            (SELECT game_id FROM game WHERE home_team_id = :team_id OR away_team_id = :team_id)')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock
            ->expects($this->at(1))
            ->method('prepare')
            ->with('DELETE FROM `game` WHERE home_team_id = :team_id OR away_team_id = :team_id')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock
            ->expects($this->at(2))
            ->method('prepare')
            ->with('DELETE FROM `follow` WHERE team_id = :team_id')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock
            ->expects($this->at(3))
            ->method('prepare')
            ->with('DELETE FROM `team` WHERE team_id = :team_id')
            ->willReturn($this->pdoStatementMock);

        // execute method called 4 times
        $this->pdoStatementMock
            ->expects($this->exactly(4))
            ->method('execute')
            ->with([':team_id' => $event->getAggregateId()]);

        $this->projection->projectTeamDeleted($event);
    }
}
