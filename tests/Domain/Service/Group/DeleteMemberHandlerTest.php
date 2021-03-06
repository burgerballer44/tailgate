<?php

namespace Tailgate\Test\Domain\Service\Group;

use PHPUnit\Framework\TestCase;
use Tailgate\Application\Command\Group\DeleteMemberCommand;
use Tailgate\Domain\Model\Group\Group;
use Tailgate\Domain\Model\Group\GroupId;
use Tailgate\Domain\Model\Group\GroupRepositoryInterface;
use Tailgate\Domain\Model\Group\MemberDeleted;
use Tailgate\Domain\Model\Group\MemberId;
use Tailgate\Domain\Model\User\UserId;
use Tailgate\Domain\Service\Group\DeleteMemberHandler;

class DeleteMemberHandlerTest extends TestCase
{
    private $groupId = 'groupId';
    private $memberId;
    private $userId = 'userId';
    private $groupName = 'groupName';
    private $groupInviteCode = 'code';
    private $group;
    private $deleteMemberCommand;

    public function setUp(): void
    {
        // create a group, add a member, and clear events
        $this->group = Group::create(
            GroupId::fromString($this->groupId),
            $this->groupName,
            $this->groupInviteCode,
            UserId::fromString($this->userId)
        );
        $this->group->addMember(UserId::fromString('userId2'));
        $this->memberId = (string) $this->group->getMembers()[1]->getMemberId();
        $this->group->clearRecordedEvents();

        $this->deleteMemberCommand = new DeleteMemberCommand(
            $this->groupId,
            $this->memberId
        );
    }

    public function testItAddsAMemberDeletedEventToTheGroupRepository()
    {
        $groupId = $this->groupId;
        $memberId = $this->memberId;
        $group = $this->group;

        $groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)->getMock();

        // the get method should be called once and will return the group
        $groupRepository->expects($this->once())->method('get')->willReturn($group);

        // the add method should be called once
        // the group object should have the MemberDeleted event
        $groupRepository->expects($this->once())->method('add')->with($this->callback(
            function ($group) use ($groupId, $memberId) {
                $events = $group->getRecordedEvents();

                return $events[0] instanceof MemberDeleted
                && $events[0]->getAggregateId()->equals(GroupId::fromString($groupId))
                && $events[0]->getMemberId()->equals(MemberId::fromString($memberId))
                && $events[0]->getOccurredOn() instanceof \DateTimeImmutable;
            }
        ));

        $deleteMemberHandler = new DeleteMemberHandler($groupRepository);

        $deleteMemberHandler->handle($this->deleteMemberCommand);
    }
}
