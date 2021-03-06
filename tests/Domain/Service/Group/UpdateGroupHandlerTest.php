<?php

namespace Tailgate\Test\Domain\Service\Group;

use PHPUnit\Framework\TestCase;
use Tailgate\Application\Command\Group\UpdateGroupCommand;
use Tailgate\Application\Validator\ValidatorInterface;
use Tailgate\Domain\Model\Group\Group;
use Tailgate\Domain\Model\Group\GroupId;
use Tailgate\Domain\Model\Group\GroupRepositoryInterface;
use Tailgate\Domain\Model\Group\GroupUpdated;
use Tailgate\Domain\Model\User\UserId;
use Tailgate\Domain\Service\Group\UpdateGroupHandler;

class UpdateGroupHandlerTest extends TestCase
{
    private $groupId = 'groupId';
    private $groupName = 'updatedgroupName';
    private $ownerId = 'updatedownerId';
    private $group;
    private $updateGroupCommand;

    public function setUp(): void
    {
        // create a group and clear events
        $this->group = Group::create(
            GroupId::fromString($this->groupId),
            'groupName',
            'code',
            UserId::fromString('userId')
        );
        $this->group->clearRecordedEvents();

        $this->updateGroupCommand = new UpdateGroupCommand(
            GroupId::fromString($this->groupId),
            $this->groupName,
            $this->ownerId
        );
    }

    public function testItAddsAGroupUpdatedEventToTheGroupRepository()
    {
        $groupId = $this->groupId;
        $groupName = $this->groupName;
        $ownerId = $this->ownerId;
        $group = $this->group;

        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)->getMock();

        // the get method should be called once and will return the group
        $groupRepository->expects($this->once())->method('get')->willReturn($group);

        // the add method should be called once
        // the group object should have the GroupUpdated event
        $groupRepository->expects($this->once())->method('add')->with($this->callback(
            function ($group) use ($groupId, $groupName, $ownerId) {
                $events = $group->getRecordedEvents();

                return $events[0] instanceof GroupUpdated
                && $events[0]->getAggregateId()->equals(GroupId::fromString($groupId))
                && $events[0]->getName() === $groupName
                && $events[0]->getOwnerId()->equals(UserId::fromString($ownerId))
                && $events[0]->getOccurredOn() instanceof \DateTimeImmutable;
            }
        ));

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->once())->method('assert')->willReturn(true);

        $updateGroupHandler = new UpdateGroupHandler($validator, $groupRepository);

        $updateGroupHandler->handle($this->updateGroupCommand);
    }
}
