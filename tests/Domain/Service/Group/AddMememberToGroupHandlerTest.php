<?php

namespace Tailgate\Test\Domain\Service\Group;

use PHPUnit\Framework\TestCase;
use Tailgate\Application\Command\Group\AddMemberToGroupCommand;
use Tailgate\Application\Validator\ValidatorInterface;
use Tailgate\Domain\Model\Group\Group;
use Tailgate\Domain\Model\Group\GroupId;
use Tailgate\Domain\Model\Group\GroupRepositoryInterface;
use Tailgate\Domain\Model\Group\MemberAdded;
use Tailgate\Domain\Model\Group\MemberId;
use Tailgate\Domain\Model\User\UserId;
use Tailgate\Domain\Service\Group\AddMemberToGroupHandler;

class AddMemberToGroupHandlerTest extends TestCase
{
    private $groupId = 'groupId';
    private $userId = 'userId';
    private $userId2 = 'userId2';
    private $groupName = 'groupName';
    private $groupInviteCode = 'code';
    private $group;
    private $addMemberToGroupCommand;

    public function setUp(): void
    {
        // create a group and clear events
        $this->group = Group::create(
            GroupId::fromString($this->groupId),
            $this->groupName,
            $this->groupInviteCode,
            UserId::fromString($this->userId)
        );
        $this->group->clearRecordedEvents();

        $this->addMemberToGroupCommand = new AddMemberToGroupCommand(
            $this->groupId,
            $this->userId2
        );
    }

    public function testItAddsAMemberAddedEventToAGroupInTheGroupRepository()
    {
        $groupId = $this->groupId;
        $userId = $this->userId2;
        $group = $this->group;

        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)->getMock();

        // the get method should be called once and will return the group
        $groupRepository->expects($this->once())->method('get')->willReturn($group);

        // the add method should be called once
        // the group object should have the MemberAdded event
        $groupRepository->expects($this->once())->method('add')->with($this->callback(
            function ($group) use ($groupId, $userId) {
                $events = $group->getRecordedEvents();

                return $events[0] instanceof MemberAdded
                && $events[0]->getAggregateId()->equals(GroupId::fromString($groupId))
                && $events[0]->getMemberId() instanceof MemberId
                && $events[0]->getUserId()->equals(UserId::fromString($userId))
                && $events[0]->getGroupRole() == Group::G_ROLE_MEMBER
                && $events[0]->getAllowMultiplePlayers() == Group::SINGLE_PLAYER
                && $events[0]->getOccurredOn() instanceof \DateTimeImmutable;
            }
        ));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('assert')->willReturn(true);

        $addMemberToGroupHandler = new AddMemberToGroupHandler($validator, $groupRepository);

        $addMemberToGroupHandler->handle($this->addMemberToGroupCommand);
    }
}
