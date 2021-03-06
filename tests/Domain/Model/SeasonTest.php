<?php

namespace Tailgate\Test\Domain\Model;

use Buttercup\Protects\AggregateHistory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tailgate\Domain\Model\Season\Game;
use Tailgate\Domain\Model\Season\GameId;
use Tailgate\Domain\Model\Season\Season;
use Tailgate\Domain\Model\Season\SeasonId;
use Tailgate\Domain\Model\Team\TeamId;

class SeasonTest extends TestCase
{
    private $seasonId;
    private $sport = Season::SPORT_FOOTBALL;
    private $seasonType = Season::SEASON_TYPE_REG;
    private $name = 'name';
    private $seasonStart;
    private $seasonEnd;

    public function setUp(): void
    {
        $this->seasonId = SeasonId::fromString('seasonId');
        $this->seasonStart = '2019-09-01';
        $this->seasonEnd = '2019-12-28';
    }

    public function testSeasonShouldBeTheSameAfterReconstitution()
    {
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $events = $season->getRecordedEvents();
        $season->clearRecordedEvents();

        $reconstitutedSeason = Season::reconstituteFrom(
            new AggregateHistory($this->seasonId, (array) $events)
        );

        $this->assertEquals(
            $season,
            $reconstitutedSeason,
            'the reconstituted season does not match the original season'
        );
    }

    public function testASeasonCanBeCreated()
    {
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );

        $this->assertEquals($this->seasonId, $season->getId());
        $this->assertEquals($this->sport, $season->getSport());
        $this->assertEquals($this->seasonType, $season->getSeasonType());
        $this->assertEquals($this->name, $season->getName());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('Y-m-d', $this->seasonStart)->format('Y-m-d H:i:s'), $season->getSeasonStart());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('Y-m-d', $this->seasonEnd)->format('Y-m-d H:i:s'), $season->getSeasonEnd());
    }

    public function testAGameIsAddedWhenGameIsAdded()
    {
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $homeTeamId = TeamId::fromString('homeTeamId');
        $awayTeamId = TeamId::fromString('awayTeamId');
        $startDate = '2019-10-01';
        $startTime = '12:12';

        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $games = $season->getGames();

        $this->assertCount(1, $games);
        $this->assertTrue($games[0] instanceof Game);
        $this->assertTrue($games[0]->getGameId() instanceof GameId);
        $this->assertTrue($games[0]->getHomeTeamId()->equals($homeTeamId));
        $this->assertTrue($games[0]->getAwayTeamId()->equals($awayTeamId));
        $this->assertEquals(\DateTimeImmutable::createFromFormat('Y-m-d', $startDate)->format('Y-m-d H:i:s'), $games[0]->getStartDate());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('H:i', $startTime)->format('Y-m-d H:i:s'), $games[0]->getStartTime());
        $this->assertTrue($games[0]->getSeasonId()->equals($this->seasonId));
    }

    public function testAGameScoreIsUpdatedToAGameWhenGameScoreIsUpdated()
    {
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $homeTeamId = TeamId::fromString('homeTeamId');
        $awayTeamId = TeamId::fromString('awayTeamId');
        $startDate = '2019-10-01';
        $startTime = '12:12';
        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $games = $season->getGames();

        $homeTeamScore = 70;
        $awayTeamScore = 60;
        $newStartDate = '2019-12-01';
        $newStartTime = '19:30';

        $season->updateGameScore(
            $games[0]->getGameId(),
            $homeTeamScore,
            $awayTeamScore,
            $newStartDate,
            $newStartTime
        );

        $this->assertCount(1, $games);
        $this->assertTrue($games[0] instanceof Game);
        $this->assertTrue($games[0]->getGameId() instanceof GameId);
        $this->assertEquals($homeTeamScore, $games[0]->getHomeTeamScore());
        $this->assertEquals($awayTeamScore, $games[0]->getAwayTeamScore());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('Y-m-d', $newStartDate)->format('Y-m-d H:i:s'), $games[0]->getStartDate());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('H:i', $newStartTime)->format('Y-m-d H:i:s'), $games[0]->getStartTime());
        $this->assertTrue($games[0]->getSeasonId()->equals($this->seasonId));
    }

    public function testExceptionThrownWhenTryingToUpdateScoreForGameThatDoesNotExist()
    {
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $homeTeamId = TeamId::fromString('homeTeamId');
        $awayTeamId = TeamId::fromString('awayTeamId');
        $startDate = '2019-10-01';
        $startTime = '12:12';
        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
 
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The game does not exist. Cannot update the game score.');
        $season->updateGameScore(
            GameId::fromString('gameThatDoesNoExist'),
            12,
            23,
            '2019-12-01',
            '12:12'
        );
    }

    public function testAGameCanBeRemovedFromASeason()
    {
        // create a season and add three games
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $homeTeamId = TeamId::fromString('homeTeamId');
        $awayTeamId = TeamId::fromString('awayTeamId');
        $startDate = '2019-10-01';
        $startTime = '12:12';

        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $games = $season->getGames();
        $this->assertCount(3, $games);

        $gameId1 = $games[0]->getGameId();
        $gameId2 = $games[1]->getGameId();
        $gameId3 = $games[2]->getGameId();

        $season->deleteGame($gameId2);

        $games = $season->getGames();

        $this->assertCount(2, $games);
        $this->assertTrue($games[0]->getGameId()->equals($gameId1));
        $this->assertTrue($games[1]->getGameId()->equals($gameId3));
    }

    public function testExceptionThrownWhenDeletingGameThatDoesNotExist()
    {
        // create a season and add three games
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $homeTeamId = TeamId::fromString('homeTeamId');
        $awayTeamId = TeamId::fromString('awayTeamId');
        $startDate = '2019-10-01';
        $startTime = '12:12';

        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $games = $season->getGames();
        $this->assertCount(3, $games);

        $gameId1 = $games[0]->getGameId();
        $gameId2 = $games[1]->getGameId();
        $gameId3 = $games[2]->getGameId();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The game does not exist. Cannot delete the game.');
        $season->deleteGame(GameId::fromString('gameThatDoesNoExist'));
    }

    public function testThereAreNoGamesInADeletedSeason()
    {
        // create a season and add a game
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );
        $homeTeamId = TeamId::fromString('homeTeamId');
        $awayTeamId = TeamId::fromString('awayTeamId');
        $startDate = '2019-10-01';
        $startTime = '12:12';

        $season->addGame($homeTeamId, $awayTeamId, $startDate, $startTime);
        $games = $season->getGames();
        $this->assertCount(1, $games);

        $season->delete();

        $games = $season->getGames();

        $this->assertCount(0, $games);
    }

    public function testASeasonCanBeUpdated()
    {
        $sport = Season::SPORT_FOOTBALL;
        $seasonType = Season::SEASON_TYPE_REG;
        $name = 'updatedname';
        $seasonStart = '2021-12-28';
        $seasonEnd = '2021-12-28';
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );

        $season->update($sport, $seasonType, $name, $seasonStart, $seasonEnd);

        $this->assertEquals($sport, $season->getSport());
        $this->assertEquals($seasonType, $season->getSeasonType());
        $this->assertEquals($name, $season->getName());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('Y-m-d', $seasonStart)->format('Y-m-d H:i:s'), $season->getSeasonStart());
        $this->assertEquals(\DateTimeImmutable::createFromFormat('Y-m-d', $seasonEnd)->format('Y-m-d H:i:s'), $season->getSeasonEnd());
    }

    public function testUpdatingSeasonWithInvalidValuesThrowsException()
    {
        $seasonStart = '2021-12-28';
        $seasonEnd = '2021-12-28';
        $season = Season::create(
            $this->seasonId,
            $this->name,
            $this->sport,
            $this->seasonType,
            $this->seasonStart,
            $this->seasonEnd
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid sport. Sport does not exist.');
        $season->update('invalidSport', Season::SEASON_TYPE_REG, 'name', $seasonStart, $seasonEnd);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid season type. Season type does not exist.');
        $season->update(Season::SPORT_FOOTBALL, 'invalidSeasonType', 'name', $seasonStart, $seasonEnd);
    }
}
